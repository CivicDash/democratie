<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding CivicDash database...');
        $this->command->newLine();

        // ==================== 1. Rôles & Permissions ====================
        $this->call(RolesAndPermissionsSeeder::class);
        $this->command->newLine();

        // ==================== 2. Territoires ====================
        $this->call(TerritoriesSeeder::class);
        $this->command->newLine();

        // ==================== 3. Secteurs budgétaires ====================
        $this->call(SectorsSeeder::class);
        $this->command->newLine();

        // ==================== 3b. Groupes parlementaires ====================
        $this->call(GroupesParlementairesSeeder::class);
        $this->command->newLine();

        // ==================== 3c. Thématiques législatives ====================
        $this->call(ThematiqueLegislationSeeder::class);
        $this->command->newLine();

        // ==================== 3c. Versions de politiques ====================
        $this->call(PolicyVersionSeeder::class);
        $this->command->newLine();

        // ==================== 3d. Achievements ====================
        $this->call(AchievementSeeder::class);
        $this->command->newLine();

        // ==================== 4. Utilisateurs de test ====================
        $this->command->info('👤 Creating test users...');

        // Admin
        $admin = User::create([
            'name' => 'Admin CivicDash',
            'email' => 'admin@civicdash.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');
        $this->command->info('✓ Admin créé : admin@civicdash.fr / password');

        // Moderator
        $moderator = User::create([
            'name' => 'Modérateur Test',
            'email' => 'moderator@civicdash.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $moderator->assignRole('moderator');
        $this->command->info('✓ Modérateur créé : moderator@civicdash.fr / password');

        // Legislator
        $legislator = User::create([
            'name' => 'Député Test',
            'email' => 'legislator@civicdash.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $legislator->assignRole('legislator');
        $this->command->info('✓ Législateur créé : legislator@civicdash.fr / password');

        // Journalist
        $journalist = User::create([
            'name' => 'Journaliste Test',
            'email' => 'journalist@civicdash.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $journalist->assignRole('journalist');
        $this->command->info('✓ Journaliste créé : journalist@civicdash.fr / password');

        // Citizen (avec profil)
        $citizen = User::create([
            'name' => 'Citoyen Test',
            'email' => 'citizen@civicdash.fr',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $citizen->assignRole('citizen');
        
        // Créer le profil citoyen
        Profile::create([
            'user_id' => $citizen->id,
            'display_name' => Profile::generateDisplayName(),
            'citizen_ref_hash' => Profile::hashCitizenRef('test-citizen-ref-123'),
            'scope' => 'national',
            'is_verified' => false,
        ]);
        $this->command->info('✓ Citoyen créé : citizen@civicdash.fr / password (avec profil)');

        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        
        $this->command->table(
            ['Compte', 'Email', 'Password', 'Rôle'],
            [
                ['Admin', 'admin@civicdash.fr', 'password', 'admin'],
                ['Modérateur', 'moderator@civicdash.fr', 'password', 'moderator'],
                ['Législateur', 'legislator@civicdash.fr', 'password', 'legislator'],
                ['Journaliste', 'journalist@civicdash.fr', 'password', 'journalist'],
                ['Citoyen', 'citizen@civicdash.fr', 'password', 'citizen'],
            ]
        );
        
        $this->command->newLine();
        $this->command->warn('⚠️  N\'oubliez pas de configurer PEPPER dans .env !');
        $this->command->info('   Commande : make pepper');
    }
}
