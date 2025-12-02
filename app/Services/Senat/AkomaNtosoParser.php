<?php

namespace App\Services\Senat;

use Illuminate\Support\Facades\Log;

/**
 * Parser pour les fichiers XML Akoma Ntoso du Sénat
 * 
 * Standard Akoma Ntoso : http://docs.oasis-open.org/legaldocml/akn-core/v1.0/
 * Documentation Sénat : https://data.senat.fr/wp-content/uploads/2021/03/akomantoso.pdf
 */
class AkomaNtosoParser
{
    private const NAMESPACE_AKN = 'http://docs.oasis-open.org/legaldocml/ns/akn/3.0';

    /**
     * Parse un fichier XML Akoma Ntoso
     */
    public function parseFile(string $filePath): ?array
    {
        if (!file_exists($filePath)) {
            Log::error("[AkomaNtosoParser] Fichier non trouvé : {$filePath}");
            return null;
        }

        $content = file_get_contents($filePath);
        return $this->parseContent($content);
    }

    /**
     * Parse le contenu XML Akoma Ntoso
     */
    public function parseContent(string $content): ?array
    {
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            Log::error("[AkomaNtosoParser] Erreur XML : " . json_encode($errors));
            libxml_clear_errors();
            return null;
        }

        // Enregistrer le namespace
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);

        // Déterminer le type de document
        $docType = $this->detectDocumentType($xml);
        
        return match ($docType) {
            'bill' => $this->parseBill($xml),
            'act' => $this->parseAct($xml),
            'amendment' => $this->parseAmendment($xml),
            'report' => $this->parseReport($xml),
            default => $this->parseGeneric($xml),
        };
    }

    /**
     * Détecte le type de document
     */
    private function detectDocumentType(\SimpleXMLElement $xml): string
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        if (!empty($xml->xpath('//akn:bill'))) {
            return 'bill';
        }
        if (!empty($xml->xpath('//akn:act'))) {
            return 'act';
        }
        if (!empty($xml->xpath('//akn:amendment'))) {
            return 'amendment';
        }
        if (!empty($xml->xpath('//akn:report'))) {
            return 'report';
        }
        
        return 'generic';
    }

    /**
     * Parse une proposition/projet de loi (bill)
     */
    private function parseBill(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $bill = $xml->xpath('//akn:bill')[0] ?? null;
        
        if (!$bill) {
            return $this->parseGeneric($xml);
        }

        $data = [
            'type' => 'bill',
            'name' => (string) ($bill['name'] ?? ''),
            'meta' => $this->parseMeta($xml),
            'preface' => $this->parsePreface($xml),
            'preamble' => $this->parsePreamble($xml),
            'body' => $this->parseBody($xml),
            'conclusions' => $this->parseConclusions($xml),
        ];

        return $data;
    }

    /**
     * Parse un acte (loi adoptée)
     */
    private function parseAct(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $act = $xml->xpath('//akn:act')[0] ?? null;
        
        $data = [
            'type' => 'act',
            'name' => (string) ($act['name'] ?? ''),
            'meta' => $this->parseMeta($xml),
            'preface' => $this->parsePreface($xml),
            'body' => $this->parseBody($xml),
        ];

        return $data;
    }

    /**
     * Parse un amendement
     */
    private function parseAmendment(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        return [
            'type' => 'amendment',
            'meta' => $this->parseMeta($xml),
            'body' => $this->parseBody($xml),
        ];
    }

    /**
     * Parse un rapport
     */
    private function parseReport(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        return [
            'type' => 'report',
            'meta' => $this->parseMeta($xml),
            'preface' => $this->parsePreface($xml),
            'body' => $this->parseBody($xml),
        ];
    }

    /**
     * Parse générique pour les documents non reconnus
     */
    private function parseGeneric(\SimpleXMLElement $xml): array
    {
        return [
            'type' => 'generic',
            'meta' => $this->parseMeta($xml),
            'content' => $xml->asXML(),
        ];
    }

    /**
     * Parse les métadonnées
     */
    private function parseMeta(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $meta = [
            'identification' => [],
            'references' => [],
            'lifecycle' => [],
        ];

        // Identification
        $frbrWork = $xml->xpath('//akn:FRBRWork')[0] ?? null;
        if ($frbrWork) {
            $frbrWork->registerXPathNamespace('akn', self::NAMESPACE_AKN);
            
            $thisNode = $frbrWork->xpath('akn:FRBRthis')[0] ?? null;
            $dateNode = $frbrWork->xpath('akn:FRBRdate')[0] ?? null;
            $authorNode = $frbrWork->xpath('akn:FRBRauthor')[0] ?? null;
            
            $meta['identification'] = [
                'uri' => (string) ($thisNode['value'] ?? ''),
                'date' => (string) ($dateNode['date'] ?? ''),
                'date_name' => (string) ($dateNode['name'] ?? ''),
                'author' => (string) ($authorNode['href'] ?? ''),
            ];
        }

        // Références (personnes, organisations)
        $references = $xml->xpath('//akn:references/akn:*');
        foreach ($references as $ref) {
            $meta['references'][] = [
                'type' => $ref->getName(),
                'id' => (string) ($ref['eId'] ?? ''),
                'href' => (string) ($ref['href'] ?? ''),
                'showAs' => (string) ($ref['showAs'] ?? ''),
            ];
        }

        // Lifecycle (événements)
        $events = $xml->xpath('//akn:lifecycle/akn:eventRef');
        foreach ($events as $event) {
            $meta['lifecycle'][] = [
                'date' => (string) ($event['date'] ?? ''),
                'type' => (string) ($event['type'] ?? ''),
                'source' => (string) ($event['source'] ?? ''),
            ];
        }

        return $meta;
    }

    /**
     * Parse le préface (titre, auteur, etc.)
     */
    private function parsePreface(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $preface = [];

        $docTitle = $xml->xpath('//akn:preface/akn:docTitle');
        if (!empty($docTitle)) {
            $preface['title'] = $this->extractText($docTitle[0]);
        }

        $docProponent = $xml->xpath('//akn:preface/akn:docProponent');
        if (!empty($docProponent)) {
            $preface['proponent'] = $this->extractText($docProponent[0]);
        }

        $docIntroducer = $xml->xpath('//akn:preface/akn:docIntroducer');
        if (!empty($docIntroducer)) {
            $preface['introducer'] = $this->extractText($docIntroducer[0]);
        }

        $docDate = $xml->xpath('//akn:preface/akn:docDate');
        if (!empty($docDate)) {
            $preface['date'] = (string) ($docDate[0]['date'] ?? $this->extractText($docDate[0]));
        }

        return $preface;
    }

    /**
     * Parse le préambule
     */
    private function parsePreamble(\SimpleXMLElement $xml): ?array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $preamble = $xml->xpath('//akn:preamble');
        
        if (empty($preamble)) {
            return null;
        }

        $paragraphs = [];
        $ps = $preamble[0]->xpath('.//akn:p');
        
        foreach ($ps as $p) {
            $paragraphs[] = $this->extractText($p);
        }

        return [
            'paragraphs' => $paragraphs,
            'raw' => $this->extractText($preamble[0]),
        ];
    }

    /**
     * Parse le corps du document (articles)
     */
    private function parseBody(\SimpleXMLElement $xml): array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $body = [
            'parts' => [],
            'titles' => [],
            'chapters' => [],
            'sections' => [],
            'articles' => [],
        ];

        // Articles
        $articles = $xml->xpath('//akn:article');
        foreach ($articles as $article) {
            $body['articles'][] = $this->parseArticle($article);
        }

        // Parties
        $parts = $xml->xpath('//akn:part');
        foreach ($parts as $part) {
            $body['parts'][] = [
                'id' => (string) ($part['eId'] ?? ''),
                'num' => $this->extractText($part->xpath('akn:num')[0] ?? null),
                'heading' => $this->extractText($part->xpath('akn:heading')[0] ?? null),
            ];
        }

        // Titres
        $titles = $xml->xpath('//akn:title');
        foreach ($titles as $title) {
            // Éviter de confondre avec docTitle
            if ($title->xpath('parent::akn:preface')) {
                continue;
            }
            $body['titles'][] = [
                'id' => (string) ($title['eId'] ?? ''),
                'num' => $this->extractText($title->xpath('akn:num')[0] ?? null),
                'heading' => $this->extractText($title->xpath('akn:heading')[0] ?? null),
            ];
        }

        // Chapitres
        $chapters = $xml->xpath('//akn:chapter');
        foreach ($chapters as $chapter) {
            $body['chapters'][] = [
                'id' => (string) ($chapter['eId'] ?? ''),
                'num' => $this->extractText($chapter->xpath('akn:num')[0] ?? null),
                'heading' => $this->extractText($chapter->xpath('akn:heading')[0] ?? null),
            ];
        }

        return $body;
    }

    /**
     * Parse un article
     */
    private function parseArticle(\SimpleXMLElement $article): array
    {
        $article->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $numNode = $article->xpath('akn:num')[0] ?? null;
        $headingNode = $article->xpath('akn:heading')[0] ?? null;
        $contentNode = $article->xpath('akn:content')[0] ?? null;
        
        $paragraphs = [];
        $alineas = $article->xpath('.//akn:alinea | .//akn:paragraph');
        
        foreach ($alineas as $alinea) {
            $paragraphs[] = [
                'id' => (string) ($alinea['eId'] ?? ''),
                'text' => $this->extractText($alinea),
            ];
        }

        // Si pas d'alinéas structurés, prendre le contenu brut
        if (empty($paragraphs) && $contentNode) {
            $ps = $contentNode->xpath('.//akn:p');
            foreach ($ps as $p) {
                $paragraphs[] = [
                    'id' => (string) ($p['eId'] ?? ''),
                    'text' => $this->extractText($p),
                ];
            }
        }

        return [
            'id' => (string) ($article['eId'] ?? ''),
            'num' => $this->extractText($numNode),
            'heading' => $this->extractText($headingNode),
            'paragraphs' => $paragraphs,
            'content' => $contentNode ? $this->extractText($contentNode) : null,
        ];
    }

    /**
     * Parse les conclusions
     */
    private function parseConclusions(\SimpleXMLElement $xml): ?array
    {
        $xml->registerXPathNamespace('akn', self::NAMESPACE_AKN);
        
        $conclusions = $xml->xpath('//akn:conclusions');
        
        if (empty($conclusions)) {
            return null;
        }

        return [
            'content' => $this->extractText($conclusions[0]),
        ];
    }

    /**
     * Extrait le texte d'un élément XML
     */
    private function extractText(?\SimpleXMLElement $element): ?string
    {
        if ($element === null) {
            return null;
        }

        // Récupérer le contenu texte sans les balises
        $text = strip_tags($element->asXML());
        
        // Nettoyer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text ?: null;
    }

    /**
     * Extrait les auteurs/signataires d'un texte
     */
    public function extractAuthors(array $parsedData): array
    {
        $authors = [];

        // Depuis les références
        foreach ($parsedData['meta']['references'] ?? [] as $ref) {
            if ($ref['type'] === 'TLCPerson') {
                // Extraire le matricule depuis l'href
                // Format: /ontology/person/fr/senateur/20032T
                preg_match('/senateur\/(\w+)$/', $ref['href'], $matches);
                
                $authors[] = [
                    'id' => $ref['id'],
                    'matricule' => $matches[1] ?? null,
                    'name' => $ref['showAs'],
                ];
            }
        }

        // Depuis le préface
        if (!empty($parsedData['preface']['proponent'])) {
            // Parser "Présenté par M. Jean DUPONT, Sénateur"
            preg_match('/(?:M\.|Mme)\s+(\w+)\s+(\w+)/u', $parsedData['preface']['proponent'], $matches);
            
            if ($matches) {
                $authors[] = [
                    'id' => null,
                    'matricule' => null,
                    'name' => "{$matches[1]} {$matches[2]}",
                    'from_preface' => true,
                ];
            }
        }

        return $authors;
    }

    /**
     * Extrait les statistiques du document
     */
    public function extractStats(array $parsedData): array
    {
        return [
            'type' => $parsedData['type'] ?? 'unknown',
            'articles_count' => count($parsedData['body']['articles'] ?? []),
            'parts_count' => count($parsedData['body']['parts'] ?? []),
            'chapters_count' => count($parsedData['body']['chapters'] ?? []),
            'references_count' => count($parsedData['meta']['references'] ?? []),
            'has_preamble' => !empty($parsedData['preamble']),
            'has_conclusions' => !empty($parsedData['conclusions']),
        ];
    }
}

