<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministeres', function (Blueprint $table) {
            if (!Schema::hasColumn('ministeres', 'slug')) {
                $table->string('slug')->nullable()->after('nom');
            }
            if (!Schema::hasColumn('ministeres', 'site_web')) {
                $table->string('site_web', 500)->nullable()->after('actif');
            }
            if (!Schema::hasColumn('ministeres', 'adresse')) {
                $table->text('adresse')->nullable()->after('site_web');
            }
            if (!Schema::hasColumn('ministeres', 'telephone')) {
                $table->string('telephone', 50)->nullable()->after('adresse');
            }
            if (!Schema::hasColumn('ministeres', 'description')) {
                $table->text('description')->nullable()->after('telephone');
            }
        });

        // Ajouter uid_an et uid_senat à la table ministres si pas présents
        Schema::table('ministres', function (Blueprint $table) {
            if (!Schema::hasColumn('ministres', 'slug')) {
                $table->string('slug')->nullable()->after('nom');
            }
            if (!Schema::hasColumn('ministres', 'uid_an')) {
                $table->string('uid_an', 50)->nullable()->after('twitter');
            }
            if (!Schema::hasColumn('ministres', 'uid_senat')) {
                $table->string('uid_senat', 50)->nullable()->after('uid_an');
            }
            if (!Schema::hasColumn('ministres', 'sexe')) {
                $table->string('sexe', 10)->nullable()->after('photo_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ministeres', function (Blueprint $table) {
            $table->dropColumn(['slug', 'site_web', 'adresse', 'telephone', 'description']);
        });

        Schema::table('ministres', function (Blueprint $table) {
            $table->dropColumn(['slug', 'uid_an', 'uid_senat', 'sexe']);
        });
    }
};
