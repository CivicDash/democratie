<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TextualReferenceParser
{
    /**
     * Extraire toutes les références juridiques d'un texte
     */
    public function parse(string $text): Collection
    {
        $references = collect();
        
        // Pattern 1: "article L. 123-4 du code civil"
        $references = $references->merge($this->extractFullReferences($text));
        
        // Pattern 2: "L.123-4 CSP" (avec acronyme)
        $references = $references->merge($this->extractAcronymReferences($text));
        
        // Pattern 3: "articles L. 123-4 à L. 123-8"
        $references = $references->merge($this->extractRangeReferences($text));
        
        // Dédupliquer
        return $references->unique(function ($ref) {
            return $ref['reference'] . '_' . $ref['code_name'];
        })->values();
    }

    /**
     * Pattern 1: Références complètes
     * Ex: "l'article L. 123-4 du code civil"
     */
    private function extractFullReferences(string $text): Collection
    {
        $references = collect();
        
        // Pattern regex pour capturer les références
        $pattern = '/(?:l\'article|l\'art\.|article|art\.)\s+([LRDA]\.?\s*[\d-]+(?:-\d+)*)\s+(?:du|de\s+la)\s+(code[^,.\n]+)/iu';
        
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        
        foreach ($matches as $match) {
            $references->push([
                'reference' => $this->normalizeReference($match[1][0]),
                'code_name' => $this->normalizeCodeName($match[2][0]),
                'position_start' => $match[0][1],
                'position_end' => $match[0][1] + strlen($match[0][0]),
                'matched_text' => $match[0][0],
                'type' => $this->getArticleType($match[1][0]),
            ]);
        }
        
        return $references;
    }

    /**
     * Pattern 2: Références avec acronyme
     * Ex: "L.123-4 CSP", "R. 456-7 du CCT"
     */
    private function extractAcronymReferences(string $text): Collection
    {
        $references = collect();
        
        $pattern = '/\b([LRDA]\.?\s*[\d-]+(?:-\d+)*)\s+(?:du\s+)?([A-Z]{2,4})\b/';
        
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        
        $legifranceService = app(LegifranceService::class);
        
        foreach ($matches as $match) {
            $acronym = $match[2][0];
            $codeName = $legifranceService->expandCodeAcronym($acronym);
            
            if ($codeName) {
                $references->push([
                    'reference' => $this->normalizeReference($match[1][0]),
                    'code_name' => $codeName,
                    'position_start' => $match[0][1],
                    'position_end' => $match[0][1] + strlen($match[0][0]),
                    'matched_text' => $match[0][0],
                    'type' => $this->getArticleType($match[1][0]),
                ]);
            }
        }
        
        return $references;
    }

    /**
     * Pattern 3: Plages d'articles
     * Ex: "articles L. 123-4 à L. 123-8 du code civil"
     */
    private function extractRangeReferences(string $text): Collection
    {
        $references = collect();
        
        $pattern = '/(?:articles|art\.)\s+([LRDA]\.?\s*[\d-]+(?:-\d+)*)\s+(?:à|au)\s+([LRDA]\.?\s*[\d-]+(?:-\d+)*)\s+(?:du|de\s+la)\s+(code[^,.\n]+)/iu';
        
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        
        foreach ($matches as $match) {
            $startRef = $this->normalizeReference($match[1][0]);
            $endRef = $this->normalizeReference($match[2][0]);
            $codeName = $this->normalizeCodeName($match[3][0]);
            
            // Ajouter la référence de début et de fin (on pourrait aussi générer toute la plage)
            $references->push([
                'reference' => $startRef,
                'code_name' => $codeName,
                'position_start' => $match[0][1],
                'position_end' => $match[0][1] + strlen($match[0][0]),
                'matched_text' => $match[0][0],
                'type' => $this->getArticleType($match[1][0]),
                'is_range_start' => true,
                'range_end' => $endRef,
            ]);
            
            $references->push([
                'reference' => $endRef,
                'code_name' => $codeName,
                'position_start' => $match[0][1],
                'position_end' => $match[0][1] + strlen($match[0][0]),
                'matched_text' => $match[0][0],
                'type' => $this->getArticleType($match[2][0]),
                'is_range_end' => true,
                'range_start' => $startRef,
            ]);
        }
        
        return $references;
    }

    /**
     * Normaliser une référence d'article
     * Ex: "L.123-4" ou "L. 123-4" → "L. 123-4"
     */
    private function normalizeReference(string $reference): string
    {
        // Supprimer espaces superflus
        $reference = trim($reference);
        
        // Ajouter un espace après la lettre si absent
        $reference = preg_replace('/^([LRDA])\.?(\d)/', '$1. $2', $reference);
        
        // Normaliser les tirets
        $reference = str_replace('_', '-', $reference);
        
        return $reference;
    }

    /**
     * Normaliser un nom de code
     * Ex: "Code  Civil" → "code civil"
     */
    private function normalizeCodeName(string $codeName): string
    {
        // Supprimer espaces multiples
        $codeName = preg_replace('/\s+/', ' ', $codeName);
        
        // Trim
        $codeName = trim($codeName);
        
        // Lowercase
        $codeName = strtolower($codeName);
        
        // Supprimer ponctuation finale
        $codeName = rtrim($codeName, '.,;:');
        
        return $codeName;
    }

    /**
     * Déterminer le type d'article (Législatif, Réglementaire, Décret, Arrêté)
     */
    private function getArticleType(string $reference): string
    {
        $firstLetter = strtoupper(substr(trim($reference), 0, 1));
        
        return match($firstLetter) {
            'L' => 'legislative',
            'R' => 'regulatory',
            'D' => 'decree',
            'A' => 'order',
            default => 'unknown',
        };
    }

    /**
     * Obtenir une description du type d'article
     */
    public static function getTypeLabel(string $type): string
    {
        return match($type) {
            'legislative' => 'Législatif (Loi)',
            'regulatory' => 'Réglementaire (Règlement)',
            'decree' => 'Décret',
            'order' => 'Arrêté',
            default => 'Type inconnu',
        };
    }

    /**
     * Vérifier si une référence est valide
     */
    public function isValidReference(string $reference): bool
    {
        $pattern = '/^[LRDA]\.\s*\d+(-\d+)*$/';
        return (bool) preg_match($pattern, $reference);
    }

    /**
     * Extraire les références d'une liste de propositions
     */
    public function parseMultiple(array $texts): Collection
    {
        $allReferences = collect();
        
        foreach ($texts as $index => $text) {
            $references = $this->parse($text);
            $references->each(function ($ref) use ($index, &$allReferences) {
                $ref['source_index'] = $index;
                $allReferences->push($ref);
            });
        }
        
        return $allReferences;
    }
}

