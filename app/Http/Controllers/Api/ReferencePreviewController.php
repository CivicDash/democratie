<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActeurAN;
use App\Models\Loi;
use App\Models\Maire;
use App\Models\ScrutinAN;
use App\Models\Senateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API pour les previews de références (@depute:, @senateur:, etc.)
 * Utilisé par le composant ReferencePreview.vue
 */
class ReferencePreviewController extends Controller
{
    /**
     * Obtenir un preview pour une référence
     */
    public function preview(string $type, string $identifier): JsonResponse
    {
        $data = match ($type) {
            'depute' => $this->getDeputePreview($identifier),
            'senateur' => $this->getSenateurPreview($identifier),
            'maire' => $this->getMairePreview($identifier),
            'loi' => $this->getLoiPreview($identifier),
            'scrutin' => $this->getScrutinPreview($identifier),
            default => null,
        };

        if (!$data) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($data);
    }

    protected function getDeputePreview(string $uid): ?array
    {
        $depute = ActeurAN::where('uid', $uid)
            ->orWhere('slug', $uid)
            ->first();

        if (!$depute) {
            return null;
        }

        return [
            'type' => 'depute',
            'nom_complet' => trim(($depute->prenom ?? '') . ' ' . ($depute->nom ?? '')),
            'photo_url' => $depute->photo_url,
            'groupe' => $depute->groupe_politique_actuel?->libelle ?? $depute->groupe_politique ?? null,
            'circonscription' => $depute->circonscription ?? null,
            'url' => route('representants.deputes.show', $depute->slug ?? $uid),
        ];
    }

    protected function getSenateurPreview(string $matricule): ?array
    {
        $senateur = Senateur::where('matricule', $matricule)->first();

        if (!$senateur) {
            return null;
        }

        return [
            'type' => 'senateur',
            'nom_complet' => trim(($senateur->prenom ?? '') . ' ' . ($senateur->nom ?? '')),
            'photo_url' => $senateur->photo_url,
            'groupe' => $senateur->groupe_politique ?? null,
            'circonscription' => $senateur->departement_nom ?? $senateur->departement ?? null,
            'url' => route('representants.senateurs.show', $matricule),
        ];
    }

    protected function getMairePreview(string $id): ?array
    {
        $maire = Maire::find($id);

        if (!$maire) {
            return null;
        }

        return [
            'type' => 'maire',
            'nom_complet' => trim(($maire->prenom ?? '') . ' ' . ($maire->nom ?? '')),
            'photo_url' => $maire->photo_url ?? null,
            'groupe' => $maire->nuance_politique ?? null,
            'circonscription' => $maire->commune . ' (' . $maire->code_departement . ')',
            'url' => route('representants.maires.show', $id),
        ];
    }

    protected function getLoiPreview(string $loiCod): ?array
    {
        $loi = Loi::where('loicod', $loiCod)->first();

        if (!$loi) {
            return null;
        }

        return [
            'type' => 'loi',
            'titre' => $loi->loitit ?? 'Loi ' . $loiCod,
            'numero' => $loi->loinum ?? null,
            'etat' => $loi->etat_code ?? null,
            'etat_label' => $loi->etat?->libelle ?? null,
            'annee' => $loi->annee ?? null,
            'url' => route('lois.show', $loiCod),
        ];
    }

    protected function getScrutinPreview(string $numero): ?array
    {
        $scrutin = ScrutinAN::where('numero', $numero)->first();

        if (!$scrutin) {
            return null;
        }

        return [
            'type' => 'scrutin',
            'titre' => $scrutin->titre ?? $scrutin->objet ?? 'Scrutin n°' . $numero,
            'pour' => $scrutin->nombre_pour ?? 0,
            'contre' => $scrutin->nombre_contre ?? 0,
            'abstention' => $scrutin->nombre_abstention ?? 0,
            'date' => $scrutin->date_scrutin?->format('d/m/Y') ?? null,
            'url' => route('scrutins.show', $numero),
        ];
    }

    /**
     * Résoudre plusieurs références en une seule requête
     */
    public function resolveMultiple(Request $request): JsonResponse
    {
        $references = $request->input('references', []);
        $results = [];

        foreach ($references as $ref) {
            $type = $ref['type'] ?? null;
            $identifier = $ref['identifier'] ?? null;

            if ($type && $identifier) {
                $data = match ($type) {
                    'depute' => $this->getDeputePreview($identifier),
                    'senateur' => $this->getSenateurPreview($identifier),
                    'maire' => $this->getMairePreview($identifier),
                    'loi' => $this->getLoiPreview($identifier),
                    'scrutin' => $this->getScrutinPreview($identifier),
                    default => null,
                };

                $results[] = [
                    'type' => $type,
                    'identifier' => $identifier,
                    'exists' => $data !== null,
                    'data' => $data,
                ];
            }
        }

        return response()->json(['references' => $results]);
    }
}
