<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propositions', function (Blueprint $table) {
            // Lien vers un dossier législatif AN
            $table->string('dossier_legislatif_uid', 30)->nullable()->after('numero');
            
            // Lien vers un scrutin AN spécifique (optionnel)
            $table->string('scrutin_an_uid', 30)->nullable()->after('dossier_legislatif_uid');
            
            // Index pour performance
            $table->index('dossier_legislatif_uid');
            $table->index('scrutin_an_uid');
            
            // Foreign keys
            $table->foreign('dossier_legislatif_uid')
                ->references('uid')
                ->on('dossiers_legislatifs_an')
                ->onDelete('set null');
                
            $table->foreign('scrutin_an_uid')
                ->references('uid')
                ->on('scrutins_an')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('propositions', function (Blueprint $table) {
            $table->dropForeign(['dossier_legislatif_uid']);
            $table->dropForeign(['scrutin_an_uid']);
            $table->dropIndex(['dossier_legislatif_uid']);
            $table->dropIndex(['scrutin_an_uid']);
            $table->dropColumn(['dossier_legislatif_uid', 'scrutin_an_uid']);
        });
    }
};

