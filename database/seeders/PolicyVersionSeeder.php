<?php

namespace Database\Seeders;

use App\Models\PolicyVersion;
use Illuminate\Database\Seeder;

class PolicyVersionSeeder extends Seeder
{
    /**
     * Seed initial policy versions (RGPD Art. 13)
     */
    public function run(): void
    {
        // Vérifier si les policy versions existent déjà
        if (PolicyVersion::where('version', '1.0.0')->exists()) {
            $this->command->info('⏭️  Policy versions déjà existantes, skip...');
            return;
        }

        // Version initiale : Politique de confidentialité
        PolicyVersion::create([
            'version' => '1.0.0',
            'policy_type' => PolicyVersion::TYPE_PRIVACY,
            'content_summary' => 'Version initiale - Conforme RGPD : anonymat citoyen, chiffrement données, consentement FranceConnect+',
            'file_path' => 'policies/privacy-1.0.0.md',
            'is_current' => true,
            'effective_at' => now(),
        ]);

        // Version initiale : Conditions d'utilisation
        PolicyVersion::create([
            'version' => '1.0.0',
            'policy_type' => PolicyVersion::TYPE_TERMS,
            'content_summary' => 'Version initiale - Règles utilisation, modération, responsabilités',
            'file_path' => 'policies/terms-1.0.0.md',
            'is_current' => true,
            'effective_at' => now(),
        ]);

        // Version initiale : Politique des cookies
        PolicyVersion::create([
            'version' => '1.0.0',
            'policy_type' => PolicyVersion::TYPE_COOKIES,
            'content_summary' => 'Version initiale - Cookies essentiels uniquement (session, authentification)',
            'file_path' => 'policies/cookies-1.0.0.md',
            'is_current' => true,
            'effective_at' => now(),
        ]);

        $this->command->info('✓ 3 policy versions créées (privacy, terms, cookies)');
        $this->command->info('  Version : 1.0.0');
        $this->command->info('  Date : ' . now()->format('d/m/Y'));
    }
}
