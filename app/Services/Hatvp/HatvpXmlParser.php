<?php

namespace App\Services\Hatvp;

use Illuminate\Support\Facades\Log;

/**
 * Parser pour les fichiers XML de la HATVP
 * 
 * Haute Autorité pour la Transparence de la Vie Publique
 * https://www.hatvp.fr/
 */
class HatvpXmlParser
{
    private bool $skipNonPublished;

    public function __construct()
    {
        $this->skipNonPublished = config('hatvp.import.skip_non_published', true);
    }

    /**
     * Parse un fichier XML de déclaration
     */
    public function parseFile(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            Log::error("[HatvpXmlParser] Fichier non trouvé : {$filePath}");
            return null;
        }

        $content = file_get_contents($filePath);
        return $this->parseContent($content);
    }

    /**
     * Parse le contenu XML d'une déclaration
     */
    public function parseContent(string $content): ?array
    {
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            Log::error("[HatvpXmlParser] Erreur XML : " . json_encode($errors));
            libxml_clear_errors();
            return null;
        }

        return $this->parseDeclaration($xml);
    }

    /**
     * Parse une déclaration complète
     */
    private function parseDeclaration(\SimpleXMLElement $xml): array
    {
        $data = [
            // Métadonnées
            'uuid' => $this->getString($xml->uuid),
            'date_depot' => $this->parseDate($this->getString($xml->dateDepot)),
            'origine' => $this->getString($xml->origine),
            'complete' => $this->getString($xml->complete) === 'true',
            'version' => $this->getString($xml->declarationVersion),
            
            // Informations générales
            'general' => $this->parseGeneral($xml->general),
            
            // Sections d'intérêts
            'activites_consultant' => $this->parseSection($xml->activConsultantDto, 'consultant'),
            'activites_professionnelles' => $this->parseSection($xml->activProfCinqDerniereDto, 'activite_pro'),
            'activites_conjoint' => $this->parseSection($xml->activProfConjointDto, 'conjoint'),
            'fonctions_benevoles' => $this->parseSection($xml->fonctionBenevoleDto, 'benevole'),
            'mandats_electifs' => $this->parseSection($xml->mandatElectifDto, 'mandat'),
            'participations_dirigeantes' => $this->parseSection($xml->participationDirigeantDto, 'dirigeant'),
            'participations_financieres' => $this->parseSection($xml->participationFinanciereDto, 'financiere'),
            'collaborateurs' => $this->parseSection($xml->activCollaborateursDto, 'collaborateur'),
            'observations_interet' => $this->parseSection($xml->observationInteretDto, 'observation'),
            
            // Sections patrimoine
            'immeubles' => $this->parseSection($xml->immeubleDto, 'immeuble'),
            'sci' => $this->parseSection($xml->sciDto, 'sci'),
            'valeurs_non_cotees' => $this->parseSection($xml->valeursNonEnBourseDto, 'valeur'),
            'valeurs_cotees' => $this->parseSection($xml->valeursEnBourseDto, 'valeur'),
            'assurances_vie' => $this->parseSection($xml->assuranceVieDto, 'assurance'),
            'comptes_bancaires' => $this->parseSection($xml->comptesBancaireDto, 'compte'),
            'biens_divers' => $this->parseSection($xml->bienDiverDto, 'bien'),
            'vehicules' => $this->parseSection($xml->vehiculeDto, 'vehicule'),
            'fonds_commerce' => $this->parseSection($xml->fondDto, 'fond'),
            'autres_biens' => $this->parseSection($xml->autreBienDto, 'bien'),
            'biens_etrangers' => $this->parseSection($xml->bienEtrangerDto, 'bien'),
            'passif' => $this->parseSection($xml->passifDto, 'dette'),
            'revenus' => $this->parseSection($xml->revenuMandatDto, 'revenu'),
            'evenements_majeurs' => $this->parseSection($xml->evenementMajeurDto, 'evenement'),
            'observations_patrimoine' => $this->parseSection($xml->observationPatrimoineDto, 'observation'),
        ];

        // Déterminer le type de déclaration
        $data['type_declaration'] = $data['general']['type_declaration_id'] ?? null;
        $data['est_interet'] = in_array($data['type_declaration'], ['DIA', 'DIAC', 'DIAI']);
        $data['est_patrimoine'] = in_array($data['type_declaration'], ['DSP', 'DSPC', 'DSPI']);

        return $data;
    }

    /**
     * Parse les informations générales
     */
    private function parseGeneral(?\SimpleXMLElement $general): array
    {
        if (!$general) {
            return [];
        }

        return [
            // Type de déclaration
            'type_declaration_id' => $this->getString($general->typeDeclaration->id),
            'type_declaration_label' => $this->getString($general->typeDeclaration->label),
            
            // Mandat
            'mandat_label' => $this->getString($general->mandat->label),
            'type_mandat' => $this->getString($general->qualiteMandat->typeMandat),
            'code_categorie_mandat' => $this->getString($general->qualiteMandat->codCategorieMandat),
            'nom_categorie_mandat' => $this->getString($general->qualiteMandat->nomCategorieMandat),
            'code_type_mandat_fichier' => $this->getString($general->qualiteMandat->codTypeMandatFichier),
            'label_type_mandat' => $this->getString($general->qualiteMandat->labelTypeMandat),
            'label_organe' => $this->getString($general->qualiteMandat->labelOrgane),
            
            // Organe
            'code_organe' => $this->getString($general->organe->codeOrgane),
            'code_liste_organe' => $this->getString($general->organe->codeListeOrgane),
            'nom_liste_organe' => $this->getString($general->organe->nomListeOrgane),
            'label_organe_detail' => $this->getString($general->organe->labelOrgane),
            
            // Qualité
            'qualite_declarant' => $this->getString($general->qualiteDeclarant),
            'qualite_declarant_pdf' => $this->getString($general->qualiteDeclarantForPDF),
            
            // Dates
            'date_debut_mandat' => $this->parseDate($this->getString($general->dateDebutMandat)),
            'date_fin_mandat' => $this->parseDate($this->getString($general->dateFinMandat)),
            'date_derniere_declaration' => $this->parseDate($this->getString($general->dateDernDeclar)),
            
            // Régime matrimonial
            'regime_matrimonial' => $this->getString($general->regimeMatrimonial),
            'regime_matrimonial_comments' => $this->getString($general->regimeMatrimonialComments),
            
            // Déclarant
            'declarant' => [
                'civilite' => $this->getString($general->declarant->civilite),
                'nom' => $this->getString($general->declarant->nom),
                'prenom' => $this->getString($general->declarant->prenom),
                'date_naissance' => $this->parseDate($this->getString($general->declarant->dateNaissance)),
                'email' => $this->cleanNonPublished($this->getString($general->declarant->email)),
                'telephone' => $this->cleanNonPublished($this->getString($general->declarant->telephoneDec)),
                'adresse' => [
                    'voie' => $this->cleanNonPublished($this->getString($general->declarant->adresseDec->voie)),
                    'complement' => $this->cleanNonPublished($this->getString($general->declarant->adresseDec->complement)),
                    'code_postal' => $this->cleanNonPublished($this->getString($general->declarant->adresseDec->codePostal)),
                    'ville' => $this->cleanNonPublished($this->getString($general->declarant->adresseDec->ville)),
                    'pays' => $this->cleanNonPublished($this->getString($general->declarant->adresseDec->pays)),
                ],
            ],
            
            // Déclaration modificative
            'declaration_modificative' => $this->getString($general->declarationModificative) === 'true',
        ];
    }

    /**
     * Parse une section avec items
     */
    private function parseSection(?\SimpleXMLElement $section, string $type): array
    {
        if (!$section) {
            return ['neant' => true, 'items' => []];
        }

        $neant = $this->getString($section->neant) === 'true';
        $items = [];

        if (isset($section->items->items)) {
            foreach ($section->items->items as $item) {
                $parsed = $this->parseItem($item, $type);
                if ($parsed) {
                    $items[] = $parsed;
                }
            }
        }

        return [
            'neant' => $neant,
            'items' => $items,
        ];
    }

    /**
     * Parse un item selon son type
     */
    private function parseItem(\SimpleXMLElement $item, string $type): ?array
    {
        $base = [
            'motif_id' => $this->getString($item->motif->id),
            'motif_label' => $this->getString($item->motif->label),
            'commentaire' => $this->getString($item->commentaire),
            'conservee' => $this->getString($item->conservee) === 'true',
        ];

        switch ($type) {
            case 'mandat':
                return array_merge($base, [
                    'description' => $this->getString($item->descriptionMandat),
                    'date_debut' => $this->parseDate($this->getString($item->dateDebut)),
                    'date_fin' => $this->parseDate($this->getString($item->dateFin)),
                    'remunerations' => $this->parseRemunerations($item->remuneration),
                ]);

            case 'dirigeant':
                return array_merge($base, [
                    'nom_societe' => $this->getString($item->nomSociete),
                    'activite' => $this->getString($item->activite),
                    'date_debut' => $this->parseDate($this->getString($item->dateDebut)),
                    'date_fin' => $this->parseDate($this->getString($item->dateFin)),
                    'remunerations' => $this->parseRemunerations($item->remuneration),
                ]);

            case 'benevole':
                return array_merge($base, [
                    'nom_structure' => $this->getString($item->nomStructure),
                    'description_activite' => $this->getString($item->descriptionActivite),
                ]);

            case 'collaborateur':
                return array_merge($base, [
                    'nom' => $this->getString($item->nom),
                    'employeur' => $this->getString($item->employeur),
                    'description_activite' => $this->getString($item->descriptionActivite),
                ]);

            case 'financiere':
                return array_merge($base, [
                    'nom_societe' => $this->getString($item->nomSociete),
                    'evaluation' => $this->getString($item->evaluation),
                    'capital_detenu' => $this->getString($item->capitalDetenu),
                    'nombre_parts' => $this->getString($item->nombreParts),
                ]);

            case 'immeuble':
                return array_merge($base, [
                    'nature' => $this->getString($item->nature),
                    'adresse' => $this->cleanNonPublished($this->getString($item->adresse)),
                    'code_postal' => $this->getString($item->codePostal),
                    'localite' => $this->getString($item->localite),
                    'superficie_bati' => $this->getInt($item->superficieBati),
                    'superficie_non_bati' => $this->getInt($item->superficieNonBati),
                    'date_acquisition' => $this->getString($item->dateAcquisition),
                    'origine' => $this->getString($item->origine),
                    'droit_reel' => $this->getString($item->droitReel),
                    'quote_part' => $this->getString($item->quotePart),
                    'prix_acquisition' => $this->parseAmount($this->getString($item->prixAcquisition)),
                    'prix_travaux' => $this->parseAmount($this->getString($item->prixTravaux)),
                    'valeur_venale' => $this->parseAmount($this->getString($item->valeurVenale)),
                    'regime_juridique' => $this->getString($item->regimeJuridique),
                ]);

            case 'vehicule':
                return array_merge($base, [
                    'nature' => $this->getString($item->nature),
                    'marque' => $this->getString($item->marque),
                    'annee_achat' => $this->getInt($item->anneeAchat),
                    'valeur_achat' => $this->parseAmount($this->getString($item->valeurAchat)),
                    'valeur' => $this->parseAmount($this->getString($item->valeur)),
                ]);

            case 'revenu':
                return [
                    'annee' => $this->getInt($item->annee),
                    'commentaire' => $this->getString($item->commentaire),
                    'revenus' => $this->parseRevenusAnnuels($item),
                    'total_elu' => $this->parseAmount($this->getString($item->totalElu)),
                    'total_conjoint' => $this->parseAmount($this->getString($item->totalConjoint)),
                ];

            case 'dette':
                return array_merge($base, [
                    'nom_creancier' => $this->getString($item->nomCreancier),
                    'adresse_creancier' => $this->cleanNonPublished($this->getString($item->adresseCreancier)),
                    'nature' => $this->getString($item->nature),
                    'date_passif' => $this->getString($item->datePassif),
                    'objet_dette' => $this->getString($item->objetDette),
                    'montant' => $this->parseAmount($this->getString($item->montant)),
                    'duree' => $this->getString($item->duree),
                    'restant_du' => $this->parseAmount($this->getString($item->restantDu)),
                    'mensualite' => $this->parseAmount($this->getString($item->mensualite)),
                ]);

            default:
                return $base;
        }
    }

    /**
     * Parse les rémunérations
     */
    private function parseRemunerations(?\SimpleXMLElement $remuneration): array
    {
        if (!$remuneration) {
            return [];
        }

        $result = [
            'brut_net' => $this->getString($remuneration->brutNet),
            'montants' => [],
        ];

        if (isset($remuneration->montant->montant)) {
            foreach ($remuneration->montant->montant as $montant) {
                $result['montants'][] = [
                    'annee' => $this->getInt($montant->annee),
                    'montant' => $this->parseAmount($this->getString($montant->montant)),
                ];
            }
        }

        return $result;
    }

    /**
     * Parse les revenus annuels (structure spéciale)
     */
    private function parseRevenusAnnuels(\SimpleXMLElement $item): array
    {
        $types = [
            'revenuMandatItem0' => 'indemnites_elu',
            'revenuMandatItem1' => 'traitements_salaires',
            'revenuMandatItem2' => 'pensions_retraites',
            'revenuMandatItem3' => 'revenus_professionnels',
            'revenuMandatItem4' => 'revenus_capitaux_mobiliers',
            'revenuMandatItem5' => 'revenus_fonciers',
            'revenuMandatItem6' => 'autres_revenus',
            'revenuMandatItem7' => 'plus_values_mobilieres',
            'revenuMandatItem8' => 'plus_values_immobilieres',
        ];

        $revenus = [];

        foreach ($types as $key => $label) {
            if (isset($item->$key)) {
                $revenus[$label] = [
                    'type_revenu' => $this->getString($item->$key->typeRevenu),
                    'revenu_elu' => $this->parseAmount($this->getString($item->$key->revenuElu)),
                    'revenu_conjoint' => $this->parseAmount($this->getString($item->$key->revenuConjoint)),
                ];
            }
        }

        return $revenus;
    }

    /**
     * Extrait une chaîne d'un élément XML
     */
    private function getString(?\SimpleXMLElement $element): ?string
    {
        if ($element === null) {
            return null;
        }

        $value = trim((string) $element);
        return $value === '' ? null : $value;
    }

    /**
     * Extrait un entier d'un élément XML
     */
    private function getInt(?\SimpleXMLElement $element): ?int
    {
        $value = $this->getString($element);
        if ($value === null) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $value);
        return $cleaned !== '' ? (int) $cleaned : null;
    }

    /**
     * Parse une date au format français
     */
    private function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        // Format: "18/11/2024 10:30:48" ou "24/09/2024" ou "01/2018"
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $value, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        // Format: "01/2018"
        if (preg_match('/^(\d{2})\/(\d{4})$/', $value, $matches)) {
            return "{$matches[2]}-{$matches[1]}-01";
        }

        return $value;
    }

    /**
     * Parse un montant
     */
    private function parseAmount(?string $value): ?float
    {
        if (!$value) {
            return null;
        }

        // Supprimer les espaces et remplacer la virgule par un point
        $cleaned = str_replace([' ', ','], ['', '.'], $value);
        $cleaned = preg_replace('/[^0-9.]/', '', $cleaned);

        return $cleaned !== '' ? (float) $cleaned : null;
    }

    /**
     * Nettoie les données non publiées
     */
    private function cleanNonPublished(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($this->skipNonPublished && stripos($value, 'Données non publiées') !== false) {
            return null;
        }

        return trim($value);
    }

    /**
     * Vérifie si c'est un parlementaire (député ou sénateur)
     */
    public function isParlementaire(array $data): bool
    {
        $typeMandat = strtolower($data['general']['code_type_mandat_fichier'] ?? '');
        $filtres = config('hatvp.filtres_parlementaires', ['senateur', 'depute']);

        return in_array($typeMandat, $filtres);
    }

    /**
     * Extrait les informations pour le matching avec les parlementaires
     */
    public function extractMatchingInfo(array $data): array
    {
        $general = $data['general'] ?? [];
        $declarant = $general['declarant'] ?? [];

        return [
            'nom' => strtoupper($declarant['nom'] ?? ''),
            'prenom' => $declarant['prenom'] ?? '',
            'date_naissance' => $declarant['date_naissance'] ?? null,
            'type_mandat' => $general['code_type_mandat_fichier'] ?? null,
            'code_departement' => $general['code_organe'] ?? null,
            'civilite' => $declarant['civilite'] ?? null,
        ];
    }

    /**
     * Génère un résumé statistique de la déclaration
     */
    public function generateStats(array $data): array
    {
        $stats = [
            'type' => $data['type_declaration'] ?? 'unknown',
            'est_interet' => $data['est_interet'] ?? false,
            'est_patrimoine' => $data['est_patrimoine'] ?? false,
        ];

        // Compter les items non-néant
        $sections = [
            'mandats_electifs', 'fonctions_benevoles', 'participations_dirigeantes',
            'participations_financieres', 'collaborateurs', 'immeubles', 'vehicules',
        ];

        foreach ($sections as $section) {
            $sectionData = $data[$section] ?? [];
            $stats[$section . '_count'] = count($sectionData['items'] ?? []);
            $stats[$section . '_neant'] = $sectionData['neant'] ?? true;
        }

        // Calculer les totaux de revenus
        if (!empty($data['revenus']['items'])) {
            $totalRevenus = 0;
            foreach ($data['revenus']['items'] as $revenu) {
                $totalRevenus += $revenu['total_elu'] ?? 0;
            }
            $stats['total_revenus_declares'] = $totalRevenus;
        }

        return $stats;
    }
}

