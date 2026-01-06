<?php

namespace App\Console\Commands;

use App\Models\Maire;
use App\Models\MaireMandat;
use App\Models\Ville;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Synchronise les villes depuis french_postal_codes et lie les maires
 */
class SyncVilles extends Command
{
    protected $signature = 'sync:villes 
                            {--departement= : Synchro un département spécifique}
                            {--with-maires : Lier les maires aux villes}
                            {--with-mandats : Créer les mandats depuis les maires existants}
                            {--force : Recréer même si existe}';

    protected $description = 'Synchronise les villes depuis french_postal_codes et lie les maires';

    private int $created = 0;
    private int $updated = 0;
    private int $mairesLies = 0;
    private int $mandatsCrees = 0;

    public function handle(): int
    {
        $this->info('🏘️ Synchronisation des villes');
        $this->newLine();

        $departement = $this->option('departement');
        $withMaires = $this->option('with-maires');
        $withMandats = $this->option('with-mandats');

        // 1. Créer/mettre à jour les villes depuis french_postal_codes
        $this->syncVillesFromPostalCodes($departement);

        // 2. Lier les maires aux villes
        if ($withMaires) {
            $this->linkMairesToVilles();
        }

        // 3. Créer les mandats depuis les maires existants
        if ($withMandats) {
            $this->createMandatsFromMaires();
        }

        $this->newLine();
        $this->info("✅ Terminé !");
        $this->info("   Villes créées : {$this->created}");
        $this->info("   Villes mises à jour : {$this->updated}");
        if ($withMaires) {
            $this->info("   Maires liés : {$this->mairesLies}");
        }
        if ($withMandats) {
            $this->info("   Mandats créés : {$this->mandatsCrees}");
        }

        return Command::SUCCESS;
    }

    private function syncVillesFromPostalCodes(?string $departement): void
    {
        $this->info('📊 Agrégation des codes postaux par code INSEE...');

        // Requête pour agréger les codes postaux par INSEE
        $query = DB::table('french_postal_codes')
            ->select('insee_code')
            ->selectRaw("MIN(city_name) as city_name")
            ->selectRaw("MIN(postal_code) as code_postal_principal")
            ->selectRaw("STRING_AGG(DISTINCT postal_code, ',' ORDER BY postal_code) as codes_postaux")
            ->selectRaw("MIN(department_code) as department_code")
            ->selectRaw("MIN(department_name) as department_name")
            ->selectRaw("MIN(region_code) as region_code")
            ->selectRaw("MIN(region_name) as region_name")
            ->selectRaw("MIN(circonscription) as circonscription")
            ->selectRaw("MIN(epci_code) as epci_code")
            ->selectRaw("MIN(epci_nom) as epci_nom")
            ->selectRaw("AVG(latitude) as latitude")
            ->selectRaw("AVG(longitude) as longitude")
            ->selectRaw("MAX(population) as population")
            ->selectRaw("MAX(superficie) as superficie")
            ->whereNotNull('insee_code')
            ->groupBy('insee_code');

        if ($departement) {
            $query->where('department_code', $departement);
        }

        $villes = $query->get();
        $this->info("   {$villes->count()} codes INSEE trouvés");

        $bar = $this->output->createProgressBar($villes->count());
        $bar->start();

        foreach ($villes as $v) {
            $this->syncVille($v);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function syncVille($data): void
    {
        // Nettoyer le nom (enlever les numéros d'arrondissement pour le nom principal)
        $nom = preg_replace('/\s+\d{2}$/', '', $data->city_name);
        $nom = ucwords(strtolower($nom));

        // Détecter si c'est un arrondissement
        $estArrondissement = preg_match('/\s+\d{2}$/', $data->city_name);
        $villeParentInsee = null;
        
        if ($estArrondissement) {
            // Paris, Lyon, Marseille
            if (preg_match('/^751\d{2}$/', $data->insee_code)) {
                $villeParentInsee = '75056';
            } elseif (preg_match('/^6938[1-9]$/', $data->insee_code)) {
                $villeParentInsee = '69123';
            } elseif (preg_match('/^132\d{2}$/', $data->insee_code)) {
                $villeParentInsee = '13055';
            }
        }

        $codesPostaux = $data->codes_postaux ? explode(',', $data->codes_postaux) : [];

        $villeData = [
            'nom' => $nom,
            'code_postal_principal' => $data->code_postal_principal,
            'codes_postaux' => $codesPostaux,
            'departement_code' => $data->department_code,
            'departement_nom' => $data->department_name,
            'region_code' => $data->region_code,
            'region_nom' => $data->region_name,
            'circonscription' => $data->circonscription,
            'epci_code' => $data->epci_code,
            'epci_nom' => $data->epci_nom,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'population' => $data->population,
            'superficie_km2' => $data->superficie,
            'arrondissement_municipal' => $estArrondissement,
            'ville_parent_insee' => $villeParentInsee,
        ];

        $ville = Ville::where('code_insee', $data->insee_code)->first();

        if ($ville) {
            $ville->update($villeData);
            $this->updated++;
        } else {
            $villeData['code_insee'] = $data->insee_code;
            $villeData['slug'] = Str::slug($nom . '-' . $data->insee_code);
            Ville::create($villeData);
            $this->created++;
        }
    }

    private function linkMairesToVilles(): void
    {
        $this->info('👔 Liaison des maires aux villes...');

        $maires = Maire::whereNull('ville_id')
            ->whereNotNull('code_commune')
            ->get();

        $bar = $this->output->createProgressBar($maires->count());
        $bar->start();

        foreach ($maires as $maire) {
            $ville = Ville::where('code_insee', $maire->code_commune)->first();
            
            if ($ville) {
                $maire->ville_id = $ville->id;
                $maire->save();

                // Mettre à jour le maire actuel de la ville
                if ($maire->en_exercice) {
                    $ville->maire_actuel_id = $maire->id;
                    $ville->save();
                }

                $this->mairesLies++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function createMandatsFromMaires(): void
    {
        $this->info('📋 Création des mandats depuis les maires existants...');

        $maires = Maire::whereNotNull('ville_id')->get();

        $bar = $this->output->createProgressBar($maires->count());
        $bar->start();

        foreach ($maires as $maire) {
            // Vérifier si un mandat existe déjà pour ce maire/ville
            $exists = MaireMandat::where('ville_id', $maire->ville_id)
                ->where('maire_id', $maire->id)
                ->exists();

            if (!$exists) {
                MaireMandat::create([
                    'ville_id' => $maire->ville_id,
                    'maire_id' => $maire->id,
                    'nom' => $maire->nom,
                    'prenom' => $maire->prenom,
                    'sexe' => $maire->sexe ?? ($maire->civilite === 'Mme' ? 'F' : 'M'),
                    'date_debut' => $maire->debut_mandat ?? $maire->debut_fonction,
                    'date_fin' => $maire->fin_mandat,
                    'type_mandat' => 'election',
                    'annee_election' => $maire->debut_mandat 
                        ? (int) substr($maire->debut_mandat, 0, 4) 
                        : null,
                    'nuance_politique' => $maire->nuance_politique,
                    'mandature' => $maire->mandature ?? '2020-2026',
                    'est_actuel' => $maire->en_exercice ?? true,
                ]);

                $this->mandatsCrees++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
