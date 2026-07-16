<?php

namespace App\Http\Controllers\Api\Presidentielle;

use App\Http\Controllers\Controller;
use App\Models\PresidentielleSignalement;
use App\Services\Presidentielle\PresidentielleExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API publique read-only du domaine présidentielle (plan §6).
 * Aucune authentification, aucun cookie. Ne renvoie QUE le contenu publié
 * (l'exporteur applique déjà `scopePublie`). Cache HTTP agressif + ETag.
 * Partage exactement la même forme JSON que l'export statique (cohérence).
 */
class PresidentielleController extends Controller
{
    public function __construct(private PresidentielleExporter $exporter) {}

    public function candidats(Request $request): JsonResponse
    {
        $data = $this->build($request);
        $index = array_map(fn ($c) => [
            'slug' => $c['slug'],
            'nom_complet' => $c['nom_complet'],
            'parti_soutien' => $c['parti_soutien'],
            'nuance' => $c['nuance'],
            'couleur_hex' => $c['couleur_hex'],
            'statut_candidature' => $c['statut_candidature'],
            'couverture' => $c['couverture'],
        ], array_values($data['candidats']));

        return $this->cached($request, ['candidats' => $index], $data['meta']['content_hash']);
    }

    public function candidat(Request $request, string $slug): JsonResponse
    {
        $data = $this->build($request);
        if (! isset($data['candidats'][$slug])) {
            return response()->json(['message' => 'Candidat introuvable ou non publié.'], 404);
        }

        return $this->cached($request, $data['candidats'][$slug], $data['meta']['content_hash'].$slug);
    }

    public function themes(Request $request): JsonResponse
    {
        $data = $this->build($request);

        return $this->cached($request, ['themes' => $data['themes']], $data['meta']['content_hash'].'themes');
    }

    public function themeMesures(Request $request, string $slug): JsonResponse
    {
        $data = $this->build($request);
        $existe = collect($data['themes'])->contains('slug', $slug);
        if (! $existe) {
            return response()->json(['message' => 'Thème introuvable.'], 404);
        }

        $parCandidat = [];
        foreach ($data['candidats'] as $candidatSlug => $candidat) {
            $parCandidat[$candidatSlug] = $candidat['mesures_par_theme'][$slug] ?? [];
        }

        return $this->cached($request, ['theme' => $slug, 'candidats' => $parCandidat], $data['meta']['content_hash'].'tm'.$slug);
    }

    public function comparateur(Request $request): JsonResponse
    {
        $data = $this->build($request);

        $candidatsFiltre = $this->listParam($request, 'candidats');
        $themesFiltre = $this->listParam($request, 'themes');

        $comparateur = $data['comparateur'];
        if ($themesFiltre) {
            $comparateur = array_intersect_key($comparateur, array_flip($themesFiltre));
        }
        if ($candidatsFiltre) {
            $comparateur = array_map(
                fn ($ligne) => array_intersect_key($ligne, array_flip($candidatsFiltre)),
                $comparateur
            );
        }

        $etag = $data['meta']['content_hash'].'cmp'.implode(',', $candidatsFiltre).implode(',', $themesFiltre);

        return $this->cached($request, ['comparateur' => $comparateur], $etag);
    }

    /**
     * Réception d'un signalement citoyen (« Signaler une erreur »).
     * Écriture publique anonyme → ticket en file de modération (statut `nouveau`).
     * Zéro donnée passive : aucune IP ni user-agent stocké ; email facultatif.
     * Anti-spam : honeypot (`site_web`) + throttle sur la route.
     */
    public function signalements(Request $request): JsonResponse
    {
        // Honeypot : un champ leurre invisible ; s'il est rempli, c'est un bot.
        // On répond « merci » sans rien écrire (ne pas révéler le mécanisme).
        if (filled($request->input('site_web'))) {
            return response()->json(['ok' => true], 201);
        }

        $data = $request->validate([
            'type_incident' => ['required', 'in:'.implode(',', array_keys(PresidentielleSignalement::TYPES_INCIDENT))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'email' => ['nullable', 'email', 'max:255'],
            'candidat_slug' => ['nullable', 'string', 'max:160'],
            'theme_slug' => ['nullable', 'string', 'max:160'],
            'argument_ref' => ['nullable', 'string', 'max:64'],
            'contexte_url' => ['nullable', 'url', 'max:1000'],
            'content_hash' => ['nullable', 'string', 'max:128'],
        ], [
            'description.required' => 'Merci de décrire l’erreur constatée.',
            'description.min' => 'Décrivez l’erreur en quelques mots (10 caractères minimum).',
        ]);

        PresidentielleSignalement::create($data + ['statut' => 'nouveau']);

        return response()->json(['ok' => true, 'message' => 'Merci, votre signalement a bien été transmis.'], 201);
    }

    private function build(Request $request): array
    {
        $election = (string) $request->query('election', '2027');

        return $this->exporter->build($election);
    }

    /** @return array<int,string> */
    private function listParam(Request $request, string $key): array
    {
        $raw = (string) $request->query($key, '');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    private function cached(Request $request, array $payload, string $etagSource): JsonResponse
    {
        $etag = '"'.substr(hash('sha256', $etagSource), 0, 32).'"';

        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response()->json(null, 304)->setEtag(trim($etag, '"'));
        }

        return response()->json($payload)
            ->setEtag(trim($etag, '"'))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
