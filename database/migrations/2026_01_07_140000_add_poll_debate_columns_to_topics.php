<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les colonnes manquantes pour les sondages et débats
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les colonnes à topics si elles n'existent pas
        Schema::table('topics', function (Blueprint $table) {
            if (!Schema::hasColumn('topics', 'poll_type')) {
                $table->string('poll_type', 30)->nullable();
            }
            
            if (!Schema::hasColumn('topics', 'poll_max_choices')) {
                $table->tinyInteger('poll_max_choices')->nullable();
            }
            
            if (!Schema::hasColumn('topics', 'poll_show_results_before_vote')) {
                $table->boolean('poll_show_results_before_vote')->default(false);
            }
            
            if (!Schema::hasColumn('topics', 'poll_allow_change_vote')) {
                $table->boolean('poll_allow_change_vote')->default(true);
            }
            
            if (!Schema::hasColumn('topics', 'poll_ends_at')) {
                $table->timestamp('poll_ends_at')->nullable();
            }
            
            if (!Schema::hasColumn('topics', 'debate_mode')) {
                $table->boolean('debate_mode')->default(false);
            }
        });

        // Ajouter la colonne à posts si elle n'existe pas
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'debate_position')) {
                $table->string('debate_position', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $columns = ['poll_type', 'poll_max_choices', 'poll_show_results_before_vote', 
                        'poll_allow_change_vote', 'poll_ends_at', 'debate_mode'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('topics', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'debate_position')) {
                $table->dropColumn('debate_position');
            }
        });
    }
};
