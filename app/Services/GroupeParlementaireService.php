<?php

namespace App\Services;

class GroupeParlementaireService
{
    /**
     * Mapping des couleurs des groupes parlementaires (L17)
     * Source: Couleurs officielles de l'Assemblée Nationale
     */
    private const COULEURS_GROUPES = [
        // Groupes L17
        'RN' => '#1B4C8D',          // Rassemblement National - Bleu marine
        'LFI-NFP' => '#CC2443',     // La France Insoumise - NUPES - Rouge
        'SOC' => '#FF8B94',         // Socialistes - Rose
        'EPR' => '#FFD700',         // Ensemble pour la République - Or/Jaune
        'HOR' => '#0055A4',         // Horizons - Bleu
        'LR' => '#0066CC',          // Les Républicains - Bleu
        'LIOT' => '#FFB347',        // Libertés, Indépendants - Orange
        'Ecolo-NUPES' => '#00C853', // Écologistes - Vert
        'GDR-NUPES' => '#DD0000',   // Gauche démocrate et républicaine - Rouge foncé
        'DEM' => '#FF9900',         // Démocrate (MoDem) - Orange
        'NI' => '#999999',          // Non-inscrits - Gris
        
        // Anciens groupes (L15-16)
        'LaREM' => '#FFD700',
        'MODEM' => '#FF9900',
        'LT' => '#87CEEB',
        'UDI-I' => '#00CED1',
        'LR-UDI' => '#0066CC',
        'NG' => '#FF6B9D',
        'FI' => '#CC2443',
        'GDR' => '#DD0000',
        'LT-C' => '#87CEEB',
    ];

    /**
     * Obtenir la couleur d'un groupe par son sigle
     */
    public function getCouleurGroupe(string $sigle): string
    {
        return self::COULEURS_GROUPES[$sigle] ?? '#6B7280'; // Gris par défaut
    }

    /**
     * Obtenir toutes les couleurs
     */
    public function getAllCouleurs(): array
    {
        return self::COULEURS_GROUPES;
    }

    /**
     * Vérifier si un sigle existe
     */
    public function hasGroupe(string $sigle): bool
    {
        return isset(self::COULEURS_GROUPES[$sigle]);
    }

    /**
     * Normaliser un sigle (enlever espaces, majuscules)
     */
    public function normalizeSigle(string $sigle): string
    {
        return strtoupper(trim($sigle));
    }
}


