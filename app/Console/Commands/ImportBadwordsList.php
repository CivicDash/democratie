<?php

namespace App\Console\Commands;

use App\Models\BannedWord;
use App\Models\NiceWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportBadwordsList extends Command
{
    protected $signature = 'moderation:import-badwords 
                            {--source=github : Source de la liste (github ou file)}
                            {--file= : Chemin vers un fichier local}
                            {--with-nice-words : Importer aussi des mots gentils}
                            {--dry-run : Simuler sans importer}';

    protected $description = 'Importer la liste de mots bannis depuis french-badwords-list';

    private const GITHUB_URL = 'https://raw.githubusercontent.com/darwiin/french-badwords-list/master/list.txt';

    public function handle(): int
    {
        $this->info('🔧 Import des mots bannis français...');
        $this->newLine();

        // Récupérer la liste
        $words = $this->fetchWords();
        
        if (empty($words)) {
            $this->error('❌ Aucun mot trouvé dans la source');
            return Command::FAILURE;
        }

        $this->info("📋 {$words->count()} mots trouvés dans la source");

        if ($this->option('dry-run')) {
            $this->warn('🔍 Mode simulation - aucune donnée ne sera importée');
            $this->table(['Mot', 'Catégorie', 'Sévérité'], $words->take(20)->map(fn($w) => [
                $w['word'],
                $w['category'],
                $w['severity']
            ]));
            $remaining = $words->count() - 20;
            $this->info("... et {$remaining} autres mots");
            return Command::SUCCESS;
        }

        // Import des mots bannis
        $imported = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($words->count());
        $bar->start();

        foreach ($words as $wordData) {
            try {
                BannedWord::firstOrCreate(
                    ['word' => $wordData['word']],
                    [
                        'category' => $wordData['category'],
                        'severity' => $wordData['severity'],
                        'is_active' => true,
                        'is_regex' => false,
                        'notes' => 'Importé depuis french-badwords-list (GitHub)',
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$imported} mots importés");
        if ($skipped > 0) {
            $this->warn("⚠️  {$skipped} mots ignorés (déjà existants ou erreur)");
        }

        // Import des mots gentils
        if ($this->option('with-nice-words')) {
            $this->importNiceWords();
        }

        $this->newLine();
        $this->info('🎉 Import terminé !');

        return Command::SUCCESS;
    }

    private function fetchWords(): \Illuminate\Support\Collection
    {
        $source = $this->option('source');
        $content = '';

        if ($source === 'github') {
            $this->info("📥 Téléchargement depuis GitHub...");
            $response = Http::timeout(30)->get(self::GITHUB_URL);
            
            if (!$response->successful()) {
                $this->error("Erreur HTTP: {$response->status()}");
                return collect();
            }
            
            $content = $response->body();
        } elseif ($source === 'file' && $this->option('file')) {
            $filePath = $this->option('file');
            if (!file_exists($filePath)) {
                $this->error("Fichier non trouvé: {$filePath}");
                return collect();
            }
            $content = file_get_contents($filePath);
        }

        // Parser la liste
        $lines = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
        
        return collect($lines)->map(function ($word) {
            $word = trim(strtolower($word));
            
            // Déterminer la catégorie et sévérité selon le mot
            $category = $this->categorizeWord($word);
            $severity = $this->determineSeverity($word);
            
            return [
                'word' => $word,
                'category' => $category,
                'severity' => $severity,
            ];
        });
    }

    private function categorizeWord(string $word): string
    {
        // Catégorisation basique selon des patterns
        $patterns = [
            'sexisme' => ['pute', 'salope', 'connasse', 'poufiasse', 'garce'],
            'racisme' => ['negre', 'nègre', 'bougnoule', 'youpin', 'chinetoque', 'bridé'],
            'homophobie' => ['pede', 'pédé', 'gouine', 'tapette', 'fiotte', 'tarlouze'],
            'violence' => ['tuer', 'crever', 'buter', 'niquer'],
            'insulte' => ['con', 'idiot', 'debile', 'débile', 'cretin', 'crétin', 'abruti', 'imbecile', 'imbécile'],
        ];

        foreach ($patterns as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($word, $keyword)) {
                    return $category;
                }
            }
        }

        return 'insulte'; // Catégorie par défaut
    }

    private function determineSeverity(string $word): string
    {
        // Les variations leetspeak sont généralement utilisées pour contourner les filtres
        // donc on les considère comme plus sévères
        $hasLeetspeak = preg_match('/[0-9@!]/', $word);
        
        // Mots très offensants
        $highSeverity = ['negre', 'nègre', 'bougnoule', 'youpin', 'nazi', 'hitler'];
        foreach ($highSeverity as $hw) {
            if (str_contains($word, $hw)) {
                return 'high';
            }
        }

        // Mots modérément offensants avec leetspeak = high
        if ($hasLeetspeak) {
            return 'medium';
        }

        // Par défaut
        return 'medium';
    }

    private function importNiceWords(): void
    {
        $this->newLine();
        $this->info('🌸 Import des mots gentils de remplacement...');

        $niceWords = [
            // Compliments
            ['word' => 'champion', 'category' => 'compliment'],
            ['word' => 'génie', 'category' => 'compliment'],
            ['word' => 'artiste', 'category' => 'compliment'],
            ['word' => 'poète', 'category' => 'compliment'],
            ['word' => 'héros', 'category' => 'compliment'],
            ['word' => 'légende', 'category' => 'compliment'],
            ['word' => 'prodige', 'category' => 'compliment'],
            ['word' => 'virtuose', 'category' => 'compliment'],
            ['word' => 'maestro', 'category' => 'compliment'],
            ['word' => 'philosophe', 'category' => 'compliment'],
            
            // Animaux mignons
            ['word' => 'petit chaton', 'category' => 'animal'],
            ['word' => 'doux panda', 'category' => 'animal'],
            ['word' => 'gentil koala', 'category' => 'animal'],
            ['word' => 'adorable loutre', 'category' => 'animal'],
            ['word' => 'mignon hérisson', 'category' => 'animal'],
            ['word' => 'joli papillon', 'category' => 'animal'],
            ['word' => 'petit écureuil', 'category' => 'animal'],
            ['word' => 'doux lapin', 'category' => 'animal'],
            ['word' => 'sage hibou', 'category' => 'animal'],
            ['word' => 'noble licorne', 'category' => 'animal'],
            
            // Nature
            ['word' => 'rayon de soleil', 'category' => 'nature'],
            ['word' => 'arc-en-ciel', 'category' => 'nature'],
            ['word' => 'étoile filante', 'category' => 'nature'],
            ['word' => 'fleur de cerisier', 'category' => 'nature'],
            ['word' => 'brise légère', 'category' => 'nature'],
            ['word' => 'aurore boréale', 'category' => 'nature'],
            ['word' => 'cascade cristalline', 'category' => 'nature'],
            ['word' => 'clair de lune', 'category' => 'nature'],
            
            // Nourriture
            ['word' => 'petit chou', 'category' => 'nourriture'],
            ['word' => 'crème brûlée', 'category' => 'nourriture'],
            ['word' => 'macaron', 'category' => 'nourriture'],
            ['word' => 'croissant doré', 'category' => 'nourriture'],
            ['word' => 'pain au chocolat', 'category' => 'nourriture'],
            ['word' => 'madeleine', 'category' => 'nourriture'],
            ['word' => 'éclair', 'category' => 'nourriture'],
            ['word' => 'financier', 'category' => 'nourriture'],
            
            // Emojis texte
            ['word' => '✨ étoile ✨', 'category' => 'emoji'],
            ['word' => '🌈 merveille 🌈', 'category' => 'emoji'],
            ['word' => '💖 trésor 💖', 'category' => 'emoji'],
            ['word' => '🦋 papillon 🦋', 'category' => 'emoji'],
            ['word' => '🌸 fleur 🌸', 'category' => 'emoji'],
            ['word' => '🍀 chance 🍀', 'category' => 'emoji'],
            ['word' => '🌟 prodige 🌟', 'category' => 'emoji'],
            ['word' => '🎨 artiste 🎨', 'category' => 'emoji'],
            
            // Expressions positives
            ['word' => 'ami des bisounours', 'category' => 'expression'],
            ['word' => 'distributeur de câlins', 'category' => 'expression'],
            ['word' => 'ambassadeur de la gentillesse', 'category' => 'expression'],
            ['word' => 'chevalier de la bienveillance', 'category' => 'expression'],
            ['word' => 'prophète de la paix', 'category' => 'expression'],
            ['word' => 'gardien des sourires', 'category' => 'expression'],
            ['word' => 'semeur de bonheur', 'category' => 'expression'],
            ['word' => 'collectionneur de compliments', 'category' => 'expression'],
        ];

        $imported = 0;
        foreach ($niceWords as $niceWord) {
            try {
                NiceWord::firstOrCreate(
                    ['word' => $niceWord['word']],
                    [
                        'category' => $niceWord['category'],
                        'is_active' => true,
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                // Ignorer les doublons
            }
        }

        $this->info("✅ {$imported} mots gentils importés");
    }
}
