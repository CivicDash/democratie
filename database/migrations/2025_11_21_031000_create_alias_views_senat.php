<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée des alias pour votes_senat et scrutins_senat
     * (pointent vers senateurs_votes et senateurs_scrutins)
     */
    public function up(): void
    {
        // votes_senat = alias de senateurs_votes
        DB::statement("
            CREATE OR REPLACE VIEW votes_senat AS
            SELECT * FROM senateurs_votes
        ");
        
        // scrutins_senat = alias de senateurs_scrutins
        DB::statement("
            CREATE OR REPLACE VIEW scrutins_senat AS
            SELECT * FROM senateurs_scrutins
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS votes_senat");
        DB::statement("DROP VIEW IF EXISTS scrutins_senat");
    }
};

