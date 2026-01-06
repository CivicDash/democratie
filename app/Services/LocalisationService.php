<?php

namespace App\Services;

use App\Models\Maire;
use App\Models\Senateur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service unifié pour la recherche par localisation
 * Utilise french_postal_codes comme source unique
 */
class LocalisationService
{
    /**
     * Recherche par code postal ou nom de ville
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $query = trim($query);
        
        if (empty($query)) {
            return collect();
        }

        // Si c'est un code postal (5 chiffres)
        if (preg_match('/^\d{5}$/', $query)) {
            return $this->searchByPostalCode($query, $limit);
        }
        
        // Sinon recherche par nom
        return $this->searchByName($query, $limit);
    }

    /**
     * Recherche par code postal
     */
    public function searchByPostalCode(string $postalCode, int $limit = 20): Collection
    {
        return DB::table('french_postal_codes')
            ->where('postal_code', $postalCode)
            ->orderByDesc('population')
            ->limit($limit)
            ->get()
            ->map(fn($row) => $this->formatResult($row));
    }

    /**
     * Recherche par nom de ville
     */
    public function searchByName(string $name, int $limit = 20): Collection
    {
        return DB::table('french_postal_codes')
            ->where('city_name', 'ILIKE', "%{$name}%")
            ->orderByDesc('population')
            ->limit($limit)
            ->get()
            ->map(fn($row) => $this->formatResult($row));
    }

    /**
     * Obtenir les représentants d'un lieu
     */
    public function getRepresentants(string $inseeCode): array
    {
        $cacheKey = "representants_{$inseeCode}";
        
        return Cache::remember($cacheKey, 3600, function () use ($inseeCode) {
            // Récupérer les infos du lieu
            $lieu = DB::table('french_postal_codes')
                ->where('insee_code', $inseeCode)
                ->first();
            
            if (!$lieu) {
                return [];
            }

            // Gérer les cas spéciaux (Paris, Lyon, Marseille avec arrondissements)
            $communeCode = $this->getCommuneCode($inseeCode);
            
            return [
                'lieu' => $this->formatResult($lieu),
                'maire' => $this->getMaire($communeCode),
                'senateurs' => $this->getSenateurs($lieu->department_code),
                'deputes' => $this->getDeputes($lieu->circonscription),
            ];
        });
    }

    /**
     * Convertir code arrondissement en code commune
     * Ex: 75101 (Paris 1er) → 75056 (Paris)
     */
    private function getCommuneCode(string $inseeCode): string
    {
        // Paris (75101-75120 → 75056)
        if (preg_match('/^751\d{2}$/', $inseeCode)) {
            return '75056';
        }
        
        // Lyon (69381-69389 → 69123)
        if (preg_match('/^6938[1-9]$/', $inseeCode)) {
            return '69123';
        }
        
        // Marseille (13201-13216 → 13055)
        if (preg_match('/^132\d{2}$/', $inseeCode)) {
            return '13055';
        }

        return $inseeCode;
    }

    /**
     * Récupérer le maire
     */
    private function getMaire(string $communeCode): ?array
    {
        $maire = Maire::where('code_commune', $communeCode)->first();
        
        if (!$maire) {
            return null;
        }

        return [
            'id' => $maire->id,
            'nom' => $maire->nom,
            'prenom' => $maire->prenom,
            'nom_complet' => trim($maire->prenom . ' ' . $maire->nom),
            'sexe' => $maire->sexe,
            'commune' => $maire->nom_commune,
            'nuance_politique' => $maire->nuance_politique,
            'photo_url' => $maire->photo_url,
            'url' => "/maires/{$maire->id}", // Route à créer
        ];
    }

    /**
     * Récupérer les sénateurs du département
     */
    private function getSenateurs(string $departmentCode): Collection
    {
        // Récupérer le nom du département pour matcher avec circonscription
        $departmentName = DB::table('french_postal_codes')
            ->where('department_code', $departmentCode)
            ->value('department_name');

        return Senateur::where(function ($query) use ($departmentCode, $departmentName) {
                // Essayer par code département
                $query->where('departement_code', $departmentCode)
                      // Ou par nom de circonscription
                      ->orWhere('circonscription', 'ILIKE', $departmentName ?? '');
            })
            ->whereNull('date_deces') // Sénateurs vivants
            ->get()
            ->map(fn($s) => [
                'matricule' => $s->matricule,
                'nom' => $s->nom,
                'prenom' => $s->prenom,
                'nom_complet' => trim($s->prenom . ' ' . $s->nom),
                'groupe' => $s->groupe_politique ?? $s->groupe_libelle,
                'photo_url' => $s->photo_url,
                'url' => "/representants/senateurs/{$s->matricule}",
            ]);
    }

    /**
     * Récupérer les députés de la circonscription
     */
    private function getDeputes(?string $circonscription): Collection
    {
        if (!$circonscription) {
            return collect();
        }

        // Format circo: "75-01" → département 75, circo 1
        if (!preg_match('/^(\d{1,3})-(\d{1,2})$/', $circonscription, $matches)) {
            return collect();
        }

        $depCode = (int) $matches[1];
        $numCirco = (int) $matches[2];

        // Utiliser deputes_circonscriptions + acteurs_an
        return DB::table('deputes_circonscriptions as dc')
            ->join('acteurs_an as a', 'dc.acteur_uid', '=', 'a.uid')
            ->where('dc.num_departement', $depCode)
            ->where('dc.num_circo', $numCirco)
            ->whereNull('dc.date_fin')
            ->select('a.uid', 'a.nom', 'a.prenom', 'dc.departement')
            ->get()
            ->map(fn($d) => [
                'uid' => $d->uid,
                'nom' => $d->nom,
                'prenom' => $d->prenom,
                'nom_complet' => trim($d->prenom . ' ' . $d->nom),
                'groupe' => $this->getDeputeGroupe($d->uid),
                'photo_url' => "https://www.assemblee-nationale.fr/dyn/deputes/{$d->uid}/photo",
                'url' => "/representants/deputes/{$d->uid}",
            ]);
    }

    /**
     * Récupérer le groupe politique d'un député
     */
    private function getDeputeGroupe(string $uid): string
    {
        // Chercher dans mandats_an si la table existe
        try {
            $groupe = DB::table('mandats_an as m')
                ->join('organes_an as o', 'm.organe_ref', '=', 'o.uid')
                ->where('m.acteur_uid', $uid)
                ->where('o.code_type', 'GP')
                ->whereNull('m.date_fin')
                ->value('o.libelle_abrev');
            
            return $groupe ?? 'NI';
        } catch (\Exception $e) {
            return 'NI';
        }
    }

    /**
     * Formater un résultat de recherche
     */
    private function formatResult($row): array
    {
        return [
            'postal_code' => $row->postal_code,
            'city_name' => $row->city_name,
            'insee_code' => $row->insee_code,
            'department_code' => $row->department_code,
            'department_name' => $row->department_name,
            'region_name' => $row->region_name,
            'circonscription' => $row->circonscription,
            'population' => $row->population,
            'latitude' => $row->latitude,
            'longitude' => $row->longitude,
        ];
    }

    /**
     * Obtenir les départements avec stats
     */
    public function getDepartements(): Collection
    {
        return Cache::remember('departements_list', 3600, function () {
            return DB::table('french_postal_codes')
                ->select('department_code', 'department_name', 'region_name')
                ->selectRaw('COUNT(*) as nb_communes')
                ->selectRaw('SUM(population) as population')
                ->groupBy('department_code', 'department_name', 'region_name')
                ->orderBy('department_code')
                ->get();
        });
    }
}
