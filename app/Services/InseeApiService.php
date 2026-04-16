<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InseeApiService
{
    private const TOKEN_URL = 'https://auth.insee.net/auth/realms/apim-gravitee/protocol/openid-connect/token';

    private const BDM_URL = 'https://api.insee.fr/series/BDM/V1';

    private const DONNEES_LOCALES_URL = 'https://api.insee.fr/donnees-locales/V0.1';

    private string $authMode;

    private string $clientId;

    private string $clientSecret;

    private string $apiKey;

    public function __construct()
    {
        $this->clientId = (string) config('services.insee.client_id', '');
        $this->clientSecret = (string) config('services.insee.client_secret', '');
        $this->apiKey = (string) config('services.insee.api_key', '');

        if (! empty($this->apiKey)) {
            $this->authMode = 'api_key';
        } elseif (! empty($this->clientId) && ! empty($this->clientSecret)) {
            $this->authMode = 'oauth2';
        } else {
            $this->authMode = 'none';
        }
    }

    /**
     * Crée un PendingRequest pré-authentifié selon le mode configuré.
     */
    private function authenticatedRequest(): ?PendingRequest
    {
        if ($this->authMode === 'api_key') {
            return Http::withHeaders([
                'X-INSEE-Api-Key-Integration' => $this->apiKey,
            ])->accept('application/json')->timeout(30);
        }

        if ($this->authMode === 'oauth2') {
            $token = $this->getAccessToken();
            if (! $token) {
                return null;
            }

            return Http::withToken($token)
                ->accept('application/json')
                ->timeout(30);
        }

        Log::warning('INSEE API: aucune méthode d\'authentification configurée (INSEE_API_KEY ou INSEE_CLIENT_ID + INSEE_CLIENT_SECRET)');

        return null;
    }

    /**
     * Obtient un Bearer token via OAuth2 client_credentials.
     * Token mis en cache 23h (durée de vie standard : 24h).
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('insee_access_token', 82800, function () {
            if (empty($this->clientId) || empty($this->clientSecret)) {
                Log::warning('INSEE API: client_id ou client_secret manquant');

                return null;
            }

            $response = Http::asForm()
                ->withBasicAuth($this->clientId, $this->clientSecret)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed()) {
                Log::error('INSEE API: échec auth OAuth2', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    /**
     * Récupère une série temporelle depuis la Banque de Données Macro-économiques.
     * Retourne un tableau de séries parsées depuis le XML SDMX.
     *
     * @param  string  $idBank  Identifiant de la série (ex: "001641607" pour la population)
     */
    public function getSeries(string $idBank, ?int $startPeriod = null, ?int $endPeriod = null): ?array
    {
        $http = $this->authenticatedRequest();
        if (! $http) {
            return null;
        }

        $url = self::BDM_URL."/data/SERIES_BDM/{$idBank}";
        $params = [];
        if ($startPeriod) {
            $params['startPeriod'] = $startPeriod;
        }
        if ($endPeriod) {
            $params['endPeriod'] = $endPeriod;
        }

        $response = $http->get($url, $params);

        if ($response->failed()) {
            Log::error("INSEE API: échec série {$idBank}", ['status' => $response->status()]);

            return null;
        }

        return $this->parseSdmxXml($response->body());
    }

    /**
     * Récupère plusieurs séries à la fois.
     *
     * @param  array  $idBanks  Tableau d'identifiants de séries
     */
    public function getMultipleSeries(array $idBanks, ?int $startPeriod = null, ?int $endPeriod = null): ?array
    {
        $http = $this->authenticatedRequest();
        if (! $http) {
            return null;
        }

        $ids = implode('+', $idBanks);
        $url = self::BDM_URL."/data/SERIES_BDM/{$ids}";
        $params = [];
        if ($startPeriod) {
            $params['startPeriod'] = $startPeriod;
        }
        if ($endPeriod) {
            $params['endPeriod'] = $endPeriod;
        }

        $response = $http->get($url, $params);

        if ($response->failed()) {
            Log::error('INSEE API: échec séries multiples', ['status' => $response->status()]);

            return null;
        }

        return $this->parseSdmxXml($response->body());
    }

    /**
     * Parse la réponse XML SDMX de l'API BDM.
     * Retourne un tableau indexé par IDBANK, chaque entrée contenant les métadonnées et observations.
     */
    private function parseSdmxXml(string $xml): ?array
    {
        try {
            $doc = new \SimpleXMLElement($xml);
            $doc->registerXPathNamespace('message', 'http://www.sdmx.org/resources/sdmxml/schemas/v2_1/message');

            $namespaces = $doc->getNamespaces(true);
            $ssNs = $namespaces['ss'] ?? $namespaces[''] ?? null;

            $dataSets = $doc->xpath('//message:DataSet');
            if (empty($dataSets)) {
                return null;
            }

            $result = [];

            foreach ($dataSets as $dataSet) {
                foreach ($dataSet->children($ssNs ?: null) as $child) {
                    if ($child->getName() !== 'Series') {
                        $seriesNodes = $dataSet->Series ?? $dataSet->children();
                        break;
                    }
                }

                foreach ($dataSet->children() as $series) {
                    if ($series->getName() !== 'Series') {
                        continue;
                    }

                    $attrs = [];
                    foreach ($series->attributes() as $k => $v) {
                        $attrs[$k] = (string) $v;
                    }

                    $idBank = $attrs['IDBANK'] ?? 'unknown';
                    $unitMult = (int) ($attrs['UNIT_MULT'] ?? 0);

                    $observations = [];
                    foreach ($series->children() as $obs) {
                        if ($obs->getName() !== 'Obs') {
                            continue;
                        }
                        $period = (string) ($obs['TIME_PERIOD'] ?? '');
                        $value = (string) ($obs['OBS_VALUE'] ?? '');

                        if ($period !== '' && $value !== '') {
                            $numericValue = (float) $value;
                            if ($unitMult > 0) {
                                $numericValue *= pow(10, $unitMult);
                            }
                            $observations[$period] = $numericValue;
                        }
                    }

                    $result[$idBank] = [
                        'metadata' => $attrs,
                        'observations' => $observations,
                    ];
                }
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('INSEE API: erreur parsing SDMX XML', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Extrait les observations (période => valeur) d'une réponse parsée.
     * Compatible avec le résultat de getSeries/getMultipleSeries.
     * Si plusieurs séries, retourne les observations de la première.
     */
    public function extractObservations(?array $data): array
    {
        if (! $data) {
            return [];
        }

        $first = reset($data);
        if (! $first || ! isset($first['observations'])) {
            return [];
        }

        return $first['observations'];
    }

    /**
     * Récupère les données locales (communes, départements, régions).
     */
    public function getDonneesLocales(string $croisement, string $zone, string $jeuDonnees): ?array
    {
        $http = $this->authenticatedRequest();
        if (! $http) {
            return null;
        }

        $url = self::DONNEES_LOCALES_URL."/donnees/{$croisement}";
        $response = $http->get($url, [
            'zone' => $zone,
            'jeuDonnees' => $jeuDonnees,
        ]);

        if ($response->failed()) {
            Log::error("INSEE API: échec données locales {$croisement}", ['status' => $response->status()]);

            return null;
        }

        return $response->json();
    }

    public function isConfigured(): bool
    {
        return $this->authMode !== 'none';
    }

    public function getAuthMode(): string
    {
        return $this->authMode;
    }
}
