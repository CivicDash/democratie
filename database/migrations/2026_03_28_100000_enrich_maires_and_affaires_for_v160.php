<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maires', function (Blueprint $table) {
            $table->foreignId('personne_politique_id')->nullable()
                ->after('liste_id')
                ->constrained('personnes_politiques')->nullOnDelete();

            $table->string('hatvp_type_mandat', 50)->nullable()->after('adresse_mairie');
            $table->string('url_hatvp')->nullable()->after('hatvp_type_mandat');

            $table->string('twitter_url')->nullable()->after('site_web');
            $table->string('facebook_url')->nullable()->after('twitter_url');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('linkedin_url')->nullable()->after('instagram_url');

            $table->boolean('fiche_enrichie')->default(false)->after('linkedin_url');

            $table->index('fiche_enrichie');
        });

        Schema::table('affaires_judiciaires', function (Blueprint $table) {
            $table->unsignedBigInteger('maire_id')->nullable()->after('senateur_matricule');
            $table->index('maire_id');
        });
    }

    public function down(): void
    {
        Schema::table('affaires_judiciaires', function (Blueprint $table) {
            $table->dropIndex(['maire_id']);
            $table->dropColumn('maire_id');
        });

        Schema::table('maires', function (Blueprint $table) {
            $table->dropForeign(['personne_politique_id']);
            $table->dropIndex(['fiche_enrichie']);
            $table->dropColumn([
                'personne_politique_id', 'hatvp_type_mandat', 'url_hatvp',
                'twitter_url', 'facebook_url', 'instagram_url', 'linkedin_url',
                'fiche_enrichie',
            ]);
        });
    }
};
