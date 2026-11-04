<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correction des tables de finances publiques « France » (04/11/2026).
 *
 *  - budget_annuel : la dette et le PIB étaient multipliés par 1 000 et les chiffres de l'État
 *    ne correspondaient à aucune source. Recettes, dépenses et résultat budgétaire de l'État :
 *    article 1er des projets de loi de règlement ; dette, PIB et ratios : Eurostat (21/10/2026).
 *  - france_economy (lignes annuelles) : PIB, croissance, dette, ratio et PIB par habitant
 *    remplacés par les séries Eurostat en base 2020 ; inflation 2025 corrigée (0,9 %).
 *    Chômage, balance commerciale et lignes trimestrielles : inchangés, non revérifiés.
 *  - france_lost_revenue : seules restent les estimations d'organismes publics ; l'État ne
 *    chiffre pas la fraude fiscale, les colonnes correspondantes sont vidées.
 *
 * Les valeurs sont les mêmes que celles des commandes import:budget-etat et
 * import:insee-economy, corrigées dans le même commit.
 */
return new class extends Migration
{
    private const MD = 1_000_000_000;

    public function up(): void
    {
        // année => [recettes, dépenses, résultat budgétaire de l'État, dette, PIB (Md€), déficit public, dette (% PIB), loi]
        $budget = [
            2020 => [216.9995, 389.6779, -178.0680, 2663.852, 2318.2762, -8.9, 114.9, 'pjl20-699'],
            2021 => [255.2147, 426.7324, -170.7394, 2828.781, 2508.1023, -6.6, 112.8, 'pjl21-787'],
            2022 => [287.4765, 445.6716, -151.4414, 2955.6391, 2653.9972, -4.7, 111.4, 'pjl22-684'],
            2023 => [286.4064, 454.5654, -172.9722, 3103.248, 2833.8264, -5.4, 109.5, 'pjl24-032'],
            2024 => [289.4669, 443.4133, -155.9300, 3306.0967, 2935.2362, -5.8, 112.6, 'pjl24-718'],
            2025 => [318.7068, 441.1943, -124.2057, 3460.111, 2991.0559, -5.1, 115.7, 'pjl25-726'],
        ];
        foreach ($budget as $annee => [$recettes, $depenses, $deficit, $dette, $pib, $deficitPib, $dettePib, $loi]) {
            DB::table('budget_annuel')->where('annee', $annee)->update([
                'recettes_nettes' => round($recettes * self::MD, 2),
                'depenses_nettes' => round($depenses * self::MD, 2),
                'deficit' => round($deficit * self::MD, 2),
                'dette_publique' => round($dette * self::MD, 2),
                'pib' => round($pib * self::MD, 2),
                'deficit_pib_pct' => $deficitPib,
                'dette_pib_pct' => $dettePib,
                'metadata' => json_encode([
                    'sources' => [
                        'etat' => "Projet de loi relatif aux résultats de la gestion de l'année {$annee}, article 1er : https://www.senat.fr/leg/{$loi}.pdf",
                        'administrations_publiques' => 'Eurostat, gov_10dd_edpt1, gov_10a_main et nama_10_gdp (mise à jour du 21/10/2026)',
                    ],
                    'definitions' => "Recettes et dépenses : budget général de l'État, nets, fonds de concours compris. Déficit : résultat budgétaire de l'État. Dette, PIB et ratios : ensemble des administrations publiques.",
                    'corrige_le' => '2026-11-04',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }

        // année => [PIB (Md€), croissance en volume (%), dette (Md€), dette (% PIB), PIB par habitant (€), inflation ou null si inchangée]
        $economie = [
            2020 => [2318.3, -7.4, 2663.9, 114.9, 34230, null],
            2021 => [2508.1, 6.9, 2828.8, 112.8, 36910, null],
            2022 => [2654.0, 2.7, 2955.6, 111.4, 38870, null],
            2023 => [2833.8, 1.6, 3103.2, 109.5, 41340, null],
            2024 => [2935.2, 1.5, 3306.1, 112.6, 42680, null],
            2025 => [2991.1, 0.8, 3460.1, 115.7, 43350, 0.9],
        ];
        foreach ($economie as $annee => [$pib, $croissance, $dette, $dettePib, $parHabitant, $inflation]) {
            DB::table('france_economy')->where('year', $annee)->whereNull('quarter')->update(array_filter([
                'gdp_billions_euros' => $pib,
                'gdp_growth_rate' => $croissance,
                'public_debt_billions_euros' => $dette,
                'public_debt_gdp_percentage' => $dettePib,
                'gdp_per_capita_euros' => $parHabitant,
                'inflation_rate' => $inflation,
                'updated_at' => now(),
            ], fn ($v) => $v !== null));
        }

        $vides = [
            'vat_fraud_billions_euros' => null, 'income_tax_fraud_billions_euros' => null,
            'corporate_tax_fraud_billions_euros' => null, 'social_fraud_billions_euros' => null,
            'tax_evasion_billions_euros' => null, 'tax_optimization_billions_euros' => null,
            'offshore_billions_euros' => null, 'total_lost_billions_euros' => null,
        ];
        $sources = 'Haut Conseil du financement de la protection sociale, « Lutte contre la fraude sociale : état des lieux et enjeux », septembre 2024 (https://www.vie-publique.fr/en-bref/295552-fraude-sociale-des-pertes-importantes-sur-les-cotisations-sociales) ; Cour des comptes, travaux sur la lutte contre la fraude fiscale';
        $note = "Fraude sociale : estimation présentée par le Haut Conseil comme un minimum théorique, dont une majorité porte sur les cotisations (travail dissimulé). Fraude fiscale : l'État n'en publie pas d'estimation, ce que relève la Cour des comptes. Les montants de 80 à 100 milliards d'euros parfois cités sont une estimation de l'écart fiscal par le syndicat Solidaires Finances publiques ; ils ne sont pas repris ici, faute d'estimation publique.";

        DB::table('france_lost_revenue')->where('year', 2023)->update(array_merge($vides, [
            'social_fraud_billions_euros' => 13.0,
            'sources' => $sources,
            'notes' => $note,
            'updated_at' => now(),
        ]));
        DB::table('france_lost_revenue')->where('year', 2024)->update(array_merge($vides, [
            'sources' => $sources,
            'notes' => 'Aucune estimation publique pour 2024 au 4 novembre 2026. '.$note,
            'updated_at' => now(),
        ]));
    }

    public function down(): void
    {
        // Pas de retour vers des données fausses : les lignes d'avant la correction ont été
        // sauvegardées sur le serveur avant la migration (copie des trois tables).
    }
};
