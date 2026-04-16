<?php

namespace App\Console\Commands;

use App\Models\CommuneAdmin;
use App\Models\CommunePage;
use App\Models\User;
use Illuminate\Console\Command;

class CommuneAssignAdmin extends Command
{
    protected $signature = 'communes:assign-admin
        {email : Email de l\'utilisateur}
        {code_insee : Code INSEE de la commune}
        {--role=maire : Role a attribuer (maire, adjoint, delegue, communication)}';

    protected $description = 'Attribuer un role d\'administration sur une page commune a un utilisateur';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("Utilisateur non trouve : {$this->argument('email')}");

            return self::FAILURE;
        }

        $page = CommunePage::where('code_insee', $this->argument('code_insee'))->first();

        if (! $page) {
            $this->error("Page commune non trouvee pour le code INSEE : {$this->argument('code_insee')}");

            return self::FAILURE;
        }

        $role = $this->option('role');

        if (! array_key_exists($role, CommuneAdmin::PERMISSIONS_PAR_ROLE)) {
            $this->error("Role invalide : {$role}. Roles disponibles : ".implode(', ', array_keys(CommuneAdmin::PERMISSIONS_PAR_ROLE)));

            return self::FAILURE;
        }

        $existing = $page->admins()->where('user_id', $user->id)->first();

        if ($existing) {
            $existing->update(['role' => $role] + CommuneAdmin::PERMISSIONS_PAR_ROLE[$role]);
            $communeNom = $page->ville ? $page->ville->nom : $page->code_insee;
            $this->info("Role mis a jour : {$user->name} est maintenant {$role} de {$communeNom}");
        } else {
            CommuneAdmin::creerAvecRole($page, $user, $role);
            $communeNom = $page->ville ? $page->ville->nom : $page->code_insee;
            $this->info("Admin ajoute : {$user->name} est maintenant {$role} de {$communeNom}");
        }

        if ($page->statut === 'auto_generee') {
            $page->update([
                'statut' => 'active',
                'reclamee_par' => $user->id,
                'reclamee_at' => now(),
                'verifiee_at' => now(),
                'verification_niveau' => 'manuelle',
            ]);
            $this->info('Page activee (statut -> active)');
        }

        return self::SUCCESS;
    }
}
