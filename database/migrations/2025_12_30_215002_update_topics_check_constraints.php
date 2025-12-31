<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Met à jour les contraintes CHECK pour utiliser les nouvelles valeurs
     * utilisées dans le système de participation citoyenne.
     */
    public function up(): void
    {
        // Supprimer les anciennes contraintes
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_scope_check');
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_status_check');
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_type_check');

        // Ajouter les nouvelles contraintes avec les bonnes valeurs
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_scope_check CHECK (scope IN ('national', 'regional', 'departemental', 'communal', 'region', 'dept'))");
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_status_check CHECK (status IN ('draft', 'pending', 'published', 'rejected', 'archived', 'open', 'closed'))");
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_type_check CHECK (type IN ('debate', 'bill', 'referendum', 'idea', 'petition', 'question'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer les anciennes contraintes
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_scope_check');
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_status_check');
        DB::statement('ALTER TABLE topics DROP CONSTRAINT IF EXISTS topics_type_check');

        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_scope_check CHECK (scope IN ('national', 'region', 'dept'))");
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_status_check CHECK (status IN ('draft', 'open', 'closed', 'archived'))");
        DB::statement("ALTER TABLE topics ADD CONSTRAINT topics_type_check CHECK (type IN ('debate', 'bill', 'referendum'))");
    }
};
