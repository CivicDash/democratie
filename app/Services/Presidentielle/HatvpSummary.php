<?php

namespace App\Services\Presidentielle;

use App\Models\HatvpDeclaration;
use App\Models\PersonnePolitique;
use Carbon\Carbon;

/**
 * Construit le résumé HATVP « façon CivicDash » (mandats, activités, participations,
 * revenus par année pour le graphe) — INTÉRÊTS (DIA) uniquement. Réutilisé par l'aperçu
 * du back-office présidentielle et par l'export public. Reprend la forme de
 * GouvernementController::show() (hatvp_summary) pour rester cohérent avec l'app.
 */
class HatvpSummary
{
    private const RELATIONS = [
        'mandatsElectifs.remunerations',
        'activitesProfessionnelles.remunerations',
        'activitesConsultant.remunerations',
        'participationsDirigeantes.remunerations',
        'collaborateurs',
        'fonctionsBenevoles',
    ];

    /**
     * Résumé pour une personne (via son rattachement HATVP).
     *
     * @return array{declarations: array, summary: array|null}
     */
    public function pourPersonne(PersonnePolitique $personne): array
    {
        $declarations = $personne->declarationsHatvp()->with(self::RELATIONS)->get();

        $url = $personne->url_hatvp
            ?? 'https://www.hatvp.fr/fiche-nominative/?declarant='
            .urlencode(strtolower($personne->nom).'-'.strtolower($personne->prenom));

        $liste = $declarations->map(fn ($d) => [
            'uuid' => $d->uuid,
            'type' => $d->type_declaration,
            'type_label' => $d->type_declaration_label,
            'date_depot' => $d->date_depot?->format('d/m/Y'),
            'type_mandat' => $d->type_mandat,
            'url' => $url,
        ])->values()->all();

        return [
            'declarations' => $liste,
            'summary' => ($first = $declarations->first()) ? $this->resume($first) : null,
        ];
    }

    /** Résumé d'une déclaration précise (aperçu BO avant rattachement). */
    public function pourUuid(string $uuid): ?array
    {
        $declaration = HatvpDeclaration::where('uuid', $uuid)->with(self::RELATIONS)->first();

        return $declaration ? $this->resume($declaration) : null;
    }

    /** Construit le résumé (DIA) à partir d'une déclaration chargée. */
    private function resume(HatvpDeclaration $d): array
    {
        $fmt = fn ($v) => $v instanceof Carbon ? $v->format('d/m/Y') : ($v ? Carbon::parse($v)->format('d/m/Y') : null);
        $rem = fn ($items) => $items->map(fn ($r) => [
            'annee' => $r->annee, 'montant' => $r->montant, 'brut_net' => $r->brut_net,
        ])->sortByDesc('annee')->values()->all();

        return [
            'declaration_date' => $d->date_depot?->format('d/m/Y'),
            'declaration_type' => $d->type_declaration_label,
            'nombre_mandats' => $d->mandatsElectifs->count(),
            'nombre_emplois' => $d->activitesProfessionnelles->count(),
            'nombre_collaborateurs' => $d->collaborateurs->count(),
            'revenus_par_annee' => $d->revenus_par_annee ?? [],
            'mandats_electifs' => $d->mandatsElectifs->map(fn ($m) => [
                'description' => $m->description_mandat ?? $m->description ?? 'Mandat électif',
                'date_debut' => $fmt($m->date_debut), 'date_fin' => $fmt($m->date_fin),
                'conserve' => $m->conservee,
                'remunerations' => $rem($m->remunerations),
                'total_remunerations' => $m->remunerations->sum('montant'),
            ])->all(),
            'activites_professionnelles' => $d->activitesProfessionnelles->map(fn ($a) => [
                'description' => $a->description ?? 'Activité professionnelle', 'employeur' => $a->employeur,
                'date_debut' => $fmt($a->date_debut), 'date_fin' => $fmt($a->date_fin), 'conservee' => $a->conservee,
                'remunerations' => $rem($a->remunerations),
                'total_remunerations' => $a->remunerations->sum('montant'),
            ])->all(),
            'activites_consultant' => $d->activitesConsultant->map(fn ($a) => [
                'description' => $a->description ?? 'Activité de conseil',
                'date_debut' => $fmt($a->date_debut), 'date_fin' => $fmt($a->date_fin),
                'remunerations' => $rem($a->remunerations),
                'total_remunerations' => $a->remunerations->sum('montant'),
            ])->all(),
            'participations_dirigeantes' => $d->participationsDirigeantes->map(fn ($p) => [
                'societe' => $p->nom_societe ?? $p->societe ?? 'Société', 'activite' => $p->activite,
                'date_debut' => $fmt($p->date_debut), 'date_fin' => $fmt($p->date_fin),
                'remunerations' => $rem($p->remunerations),
                'total_remunerations' => $p->remunerations->sum('montant'),
            ])->all(),
            'fonctions_benevoles' => $d->fonctionsBenevoles->map(fn ($f) => [
                'description' => $f->description, 'organisme' => $f->organisme,
            ])->all(),
        ];
    }
}
