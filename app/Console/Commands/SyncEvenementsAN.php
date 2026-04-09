<?php

namespace App\Console\Commands;

use App\Models\EvenementLegislatif;
use App\Models\ReunionAN;
use Illuminate\Console\Command;

class SyncEvenementsAN extends Command
{
    protected $signature = 'sync:evenements-an 
        {--fresh : Supprimer les événements AN existants avant sync}';

    protected $description = 'Synchroniser les réunions AN vers la table unifiée des événements';

    public function handle(): int
    {
        $this->info('🔄 Synchronisation réunions AN → événements législatifs');
        $this->newLine();

        if ($this->option('fresh')) {
            $count = EvenementLegislatif::an()->delete();
            $this->warn("🗑️  {$count} événements AN supprimés");
        }

        $reunions = ReunionAN::query()
            ->whereNotNull('date_debut')
            ->orderBy('date_debut')
            ->get();

        $this->info("📋 {$reunions->count()} réunions à synchroniser");

        $bar = $this->output->createProgressBar($reunions->count());
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($reunions as $reunion) {
            $event = $this->syncReunion($reunion);

            if ($event->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Synchronisation terminée:');
        $this->table(
            ['Créés', 'Mis à jour'],
            [[$created, $updated]]
        );

        return self::SUCCESS;
    }

    private function syncReunion(ReunionAN $reunion): EvenementLegislatif
    {
        // Déterminer le type
        $type = $this->determineType($reunion);

        // Générer un UID unique
        $uid = 'an-'.$reunion->uid;

        // Récupérer le nom de l'organe (tronqué à 255 chars)
        $instanceNom = $reunion->organe?->libelle ?? $reunion->format ?? 'Assemblée nationale';
        if (strlen($instanceNom) > 255) {
            $instanceNom = substr($instanceNom, 0, 252).'...';
        }
        $instanceCode = $reunion->organe_ref;

        return EvenementLegislatif::updateOrCreate(
            ['uid' => $uid],
            [
                'source' => EvenementLegislatif::SOURCE_AN,
                'type' => $type,
                'titre' => $this->buildTitre($reunion),
                'description' => $reunion->objet_reunion,
                'lieu' => $reunion->lieu,
                'date_debut' => $reunion->date_debut,
                'date_fin' => $reunion->date_fin,
                'journee_entiere' => false,
                'instance_code' => $instanceCode,
                'instance_nom' => $instanceNom,
                'organe_ref' => $reunion->organe_ref,
                'url_source' => $reunion->xsi_type ? "https://www.assemblee-nationale.fr/dyn/reunions/{$reunion->uid}" : null,
                'url_video' => null,
                'url_dossier' => null,
                'couleur' => EvenementLegislatif::COULEURS[EvenementLegislatif::SOURCE_AN],
                'icone' => EvenementLegislatif::ICONES[$type] ?? '📅',
                'statut' => $this->determineStatut($reunion),
            ]
        );
    }

    private function determineType(ReunionAN $reunion): string
    {
        $format = strtolower($reunion->format ?? '');
        $objet = strtolower($reunion->objet_reunion ?? '');

        if (str_contains($format, 'séance') || str_contains($format, 'hemicycle')) {
            return EvenementLegislatif::TYPE_SEANCE;
        }

        if (str_contains($format, 'commission') || str_contains($objet, 'commission')) {
            return EvenementLegislatif::TYPE_COMMISSION;
        }

        if (str_contains($objet, 'audition') || str_contains($format, 'audition')) {
            return EvenementLegislatif::TYPE_AUDITION;
        }

        if (str_contains($objet, 'vote') || str_contains($objet, 'scrutin')) {
            return EvenementLegislatif::TYPE_VOTE;
        }

        return EvenementLegislatif::TYPE_REUNION;
    }

    private function determineStatut(ReunionAN $reunion): string
    {
        $etat = strtolower($reunion->etat ?? '');

        if (str_contains($etat, 'annul')) {
            return EvenementLegislatif::STATUT_ANNULE;
        }

        if (str_contains($etat, 'report')) {
            return EvenementLegislatif::STATUT_REPORTE;
        }

        return EvenementLegislatif::STATUT_CONFIRME;
    }

    private function buildTitre(ReunionAN $reunion): string
    {
        $parts = [];

        if ($reunion->format) {
            $parts[] = $reunion->format;
        }

        if ($reunion->objet_reunion) {
            $objet = $reunion->objet_reunion;
            if (strlen($objet) > 200) {
                $objet = substr($objet, 0, 197).'...';
            }
            $parts[] = $objet;
        }

        if (empty($parts)) {
            return 'Réunion Assemblée nationale';
        }

        $titre = implode(' - ', $parts);

        if (strlen($titre) > 255) {
            $titre = substr($titre, 0, 252).'...';
        }

        return $titre;
    }
}
