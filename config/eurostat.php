<?php

/*
|--------------------------------------------------------------------------
| Comparaisons européennes de « Ce qu'on entend » (annexe D du dossier de sourçage)
|--------------------------------------------------------------------------
|
| Portage de `fetch_eurostat.py` (Idées reçus/, 26/09/2026) : mêmes jeux, mêmes filtres,
| mêmes calculs, mêmes arrondis. Le front n'appelle jamais Eurostat et ne calcule rien —
| un nouvel indicateur dérivé s'ajoute ICI, pas dans un composant.
|
| `calcul` :
|   - brut    : la série telle que publiée ;
|   - ratio   : série / dénominateur × facteur, année par année, arrondi à 2 décimales ;
|   - base100 : indice rebasé sur une année, arrondi à 1 décimale.
| Les statuts Eurostat (p provisoire, e estimé, b rupture…) sont conservés et, pour un
| ratio, fusionnés : ils DOIVENT être affichés sous le graphique.
|
| Les codes sont en ASCII : `repérés_situation_illegale` du script d'origine devient
| `reperes_situation_illegale`.
*/

$population = ['jeu' => 'migr_pop1ctz', 'filtres' => ['citizen' => 'TOTAL', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR']];

$indicateurs = [
    // Fiche 1 — immigration
    [
        'code' => 'part_nes_etranger', 'fiche' => 'trop-d-immigration',
        'titre' => 'Part de la population née à l\'étranger', 'unite' => '% de la population',
        'jeu' => 'migr_pop3ctb', 'filtres' => ['c_birth' => 'FOR', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR'],
        'calcul' => ['type' => 'ratio', 'facteur' => 100, 'jeu' => 'migr_pop3ctb', 'filtres' => ['c_birth' => 'TOTAL', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR']],
        'note' => 'Né à l\'étranger ≠ immigré au sens INSEE : inclut les Français nés à l\'étranger (rapatriés, enfants d\'expatriés).',
        'pertinence' => 'haute',
    ],
    [
        'code' => 'part_etrangers', 'fiche' => 'trop-d-immigration',
        'titre' => 'Part de la population de nationalité étrangère', 'unite' => '% de la population',
        'jeu' => 'migr_pop1ctz', 'filtres' => ['citizen' => 'FOR_STLS', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR'],
        'calcul' => ['type' => 'ratio', 'facteur' => 100] + $population,
        'note' => 'Étrangers et apatrides. Catégorie administrative comparable entre pays ; dépend aussi des règles de naturalisation de chaque pays.',
        'pertinence' => 'haute',
    ],
    [
        'code' => 'emigration_nationaux_pour_1000', 'fiche' => 'trop-d-immigration',
        'titre' => 'Départs de nationaux pour 1 000 habitants', 'unite' => '‰',
        'jeu' => 'migr_emi1ctz', 'filtres' => ['citizen' => 'NAT', 'agedef' => 'COMPLET', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR'],
        'calcul' => ['type' => 'ratio', 'facteur' => 1000] + $population,
        'note' => 'L\'émigration est mal mesurée partout (départs rarement déclarés) ; méthodes nationales hétérogènes.',
        'pertinence' => 'moyenne',
    ],
    [
        'code' => 'emigration_nationaux_nombre', 'fiche' => 'trop-d-immigration',
        'titre' => 'Départs de nationaux (nombre)', 'unite' => 'personnes',
        'jeu' => 'migr_emi1ctz', 'filtres' => ['citizen' => 'NAT', 'agedef' => 'COMPLET', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR'],
        'calcul' => ['type' => 'brut'],
        'note' => 'Idem.',
        'pertinence' => 'moyenne',
    ],
    [
        'code' => 'immigration_totale_pour_1000', 'fiche' => 'trop-d-immigration',
        'titre' => 'Arrivées (toutes nationalités) pour 1 000 habitants', 'unite' => '‰',
        'jeu' => 'migr_imm1ctz', 'filtres' => ['citizen' => 'TOTAL', 'agedef' => 'COMPLET', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'NR'],
        'calcul' => ['type' => 'ratio', 'facteur' => 1000] + $population,
        'note' => 'Inclut les retours de nationaux ; définitions de durée de séjour harmonisées mais sources nationales différentes.',
        'pertinence' => 'haute',
    ],
];

foreach (['NAT' => 'nés dans le pays', 'FOR' => 'nés à l\'étranger'] as $naissance => $libelle) {
    $indicateurs[] = [
        'code' => 'taux_emploi_'.strtolower($naissance), 'fiche' => 'trop-d-immigration',
        'titre' => "Taux d'emploi des 20-64 ans {$libelle}", 'unite' => '%',
        'jeu' => 'lfsa_ergacob', 'filtres' => ['unit' => 'PC', 'sex' => 'T', 'age' => 'Y20-64', 'c_birth' => $naissance],
        'calcul' => ['type' => 'brut'],
        'note' => 'Enquête Forces de travail (LFS), harmonisée.',
        'pertinence' => 'haute',
    ];
    $indicateurs[] = [
        'code' => 'taux_chomage_'.strtolower($naissance), 'fiche' => 'trop-d-immigration',
        'titre' => "Taux de chômage des 15-74 ans {$libelle}", 'unite' => '%',
        'jeu' => 'lfsa_urgacob', 'filtres' => ['unit' => 'PC', 'sex' => 'T', 'age' => 'Y15-74', 'c_birth' => $naissance],
        'calcul' => ['type' => 'brut'],
        'note' => 'Enquête Forces de travail (LFS), harmonisée.',
        'pertinence' => 'haute',
    ];
}

$illegaux = ['reason' => 'TOTAL', 'apprehen' => 'TOTAL', 'citizen' => 'TOTAL', 'sex' => 'T', 'age' => 'TOTAL', 'unit' => 'PER'];
$noteIllegaux = 'Mesure l\'activité des contrôles (une personne peut être comptée plusieurs fois), PAS le nombre de personnes en situation irrégulière.';

array_push($indicateurs,
    [
        'code' => 'reperes_situation_illegale', 'fiche' => 'trop-d-immigration',
        'titre' => 'Ressortissants de pays tiers repérés en situation illégale', 'unite' => 'personnes',
        'jeu' => 'migr_eipre', 'filtres' => $illegaux,
        'calcul' => ['type' => 'brut'],
        'note' => $noteIllegaux, 'pertinence' => 'moyenne',
    ],
    [
        'code' => 'reperes_situation_illegale_pour_1000', 'fiche' => 'trop-d-immigration',
        'titre' => 'Ressortissants de pays tiers repérés en situation illégale, pour 1 000 habitants', 'unite' => '‰',
        'jeu' => 'migr_eipre', 'filtres' => $illegaux,
        'calcul' => ['type' => 'ratio', 'facteur' => 1000] + $population,
        'note' => $noteIllegaux, 'pertinence' => 'moyenne',
    ],
    [
        'code' => 'retours_apres_obligation', 'fiche' => 'trop-d-immigration',
        'titre' => 'Ressortissants de pays tiers ayant quitté le territoire après une obligation de quitter', 'unite' => 'personnes',
        'jeu' => 'migr_eirtn', 'filtres' => ['citizen' => 'TOTAL', 'c_dest' => 'TOTAL', 'age' => 'TOTAL', 'sex' => 'T', 'unit' => 'PER'],
        'calcul' => ['type' => 'brut'],
        'note' => 'Retours effectifs, forcés ou volontaires.', 'pertinence' => 'moyenne',
    ],
    // Fiche 2 — prestations sociales
    [
        'code' => 'protection_sociale_pct_pib', 'fiche' => 'prestations-sociales-trop-cheres',
        'titre' => 'Dépenses de protection sociale', 'unite' => '% du PIB',
        'jeu' => 'tps00098', 'filtres' => ['unit' => 'PC_GDP', 'spdeps' => 'TOTAL'],
        'calcul' => ['type' => 'brut'],
        'note' => 'Dépenses publiques et privées obligatoires (SESPROS).', 'pertinence' => 'haute',
    ],
);

foreach ([
    'OLD_SRV' => 'vieillesse et survie', 'SICK_DIS' => 'maladie, santé et invalidité',
    'HOU_EXCL' => 'logement et exclusion sociale', 'FAM' => 'famille', 'UNE' => 'chômage',
] as $fonction => $libelle) {
    $indicateurs[] = [
        'code' => 'part_prestations_'.strtolower($fonction), 'fiche' => 'prestations-sociales-trop-cheres',
        'titre' => "Part des prestations : {$libelle}", 'unite' => '% des prestations',
        'jeu' => 'tps00106', 'filtres' => ['unit' => 'PC_BEN', 'spdeps' => 'SPR', 'spfunc' => $fonction],
        'calcul' => ['type' => 'brut'],
        'note' => 'Structure des prestations par risque (SESPROS).', 'pertinence' => 'haute',
    ];
}

array_push($indicateurs,
    // Fiche 4 — pauvreté
    [
        'code' => 'taux_risque_pauvrete', 'fiche' => 'pauvres-s-appauvrissent',
        'titre' => 'Taux de risque de pauvreté (seuil 60 % du médian)', 'unite' => '%',
        'jeu' => 'ilc_li02', 'filtres' => ['statinfo' => 'MED_EI', 'unit' => 'PC', 'rskpovth' => 'B_60', 'sex' => 'T', 'age' => 'TOTAL'],
        'calcul' => ['type' => 'brut'],
        'note' => 'Année d\'enquête EU-SILC = année N, revenus de N-1. Diffère du taux INSEE (ERFS) : ne pas mélanger.',
        'pertinence' => 'haute',
    ],
    // Fiche 5 — inflation
    [
        'code' => 'inflation_ipch', 'fiche' => 'inflation-explose',
        'titre' => 'Inflation (IPCH), moyenne annuelle', 'unite' => '%',
        'jeu' => 'prc_hicp_aind', 'filtres' => ['unit' => 'RCH_A_AVG', 'coicop' => 'CP00'],
        'calcul' => ['type' => 'brut'],
        'note' => 'IPCH harmonisé ≠ IPC INSEE (écart de pondération, santé notamment).', 'pertinence' => 'haute',
    ],
);

foreach (['CP00' => 'ensemble', 'CP011' => 'alimentation'] as $coicop => $libelle) {
    $indicateurs[] = [
        'code' => 'niveau_prix_'.strtolower($coicop).'_base2021', 'fiche' => 'inflation-explose',
        'titre' => "Niveau des prix ({$libelle}), base 100 en 2021", 'unite' => 'indice',
        'jeu' => 'prc_hicp_aind', 'filtres' => ['unit' => 'INX_A_AVG', 'coicop' => $coicop],
        'calcul' => ['type' => 'base100', 'annee' => 2021],
        'note' => 'Indice annuel moyen rebasé : montre le niveau atteint, pas le rythme.', 'pertinence' => 'haute',
    ];
}

$indicateurs[] = [
    // Fiche 8 — services publics
    'code' => 'lits_hopital_100k', 'fiche' => 'plus-de-service-public',
    'titre' => 'Lits d\'hôpital pour 100 000 habitants', 'unite' => 'lits / 100 000 hab.',
    'jeu' => 'hlth_rs_bds1', 'filtres' => ['unit' => 'P_HTHAB', 'facility' => 'HBEDT', 'hlthcare' => 'TOTAL'],
    'calcul' => ['type' => 'brut'],
    'note' => 'Tous lits disponibles ; ne compte pas les places sans nuitée. Organisation des soins différente selon les pays.',
    'pertinence' => 'haute',
];

// Fiches 8 et 9 — dépenses publiques
foreach ([
    ['TE', 'TOTAL', 'depenses_publiques_pct_pib', 'Dépenses publiques totales', 'trop-de-fonctionnaires'],
    ['D1', 'TOTAL', 'remuneration_agents_publics_pct_pib', 'Rémunération des agents publics', 'trop-de-fonctionnaires'],
    ['TE', 'GF07', 'depenses_publiques_sante_pct_pib', 'Dépenses publiques de santé', 'plus-de-service-public'],
] as [$item, $cofog, $code, $titre, $fiche]) {
    $indicateurs[] = [
        'code' => $code, 'fiche' => $fiche, 'titre' => $titre, 'unite' => '% du PIB',
        'jeu' => 'gov_10a_exp', 'filtres' => ['unit' => 'PC_GDP', 'sector' => 'S13', 'cofog99' => $cofog, 'na_item' => $item],
        'calcul' => ['type' => 'brut'],
        'note' => 'Comptabilité nationale, classification COFOG.', 'pertinence' => 'haute',
    ];
}

return [
    'api' => 'https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/',
    // France, les trois autres plus grandes économies de la zone euro, et la moyenne UE-27.
    'panel' => ['FR', 'DE', 'IT', 'ES', 'EU27_2020'],
    'depuis' => 2010,
    'legende_statuts' => [
        'p' => 'provisoire', 'e' => 'estimé', 'b' => 'rupture de série',
        'u' => 'faible fiabilité', 'd' => 'définition différente', 'c' => 'confidentiel',
    ],
    'indicateurs' => $indicateurs,
];
