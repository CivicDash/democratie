<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table unifiée pour tous les événements législatifs (AN + Sénat + Élysée)
     */
    public function up(): void
    {
        Schema::create('evenements_legislatifs', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // Identifiant unique (préfixé par source)
            
            // Source et type
            $table->string('source'); // 'an', 'senat', 'elysee'
            $table->string('type'); // 'seance', 'commission', 'reunion', 'vote', 'audition', 'autre'
            
            // Informations principales
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('lieu')->nullable();
            
            // Dates
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->boolean('journee_entiere')->default(false);
            
            // Instance/Organe
            $table->string('instance_code')->nullable(); // 'COM-LOIS', 'COMFINANCES', etc.
            $table->string('instance_nom')->nullable(); // 'Commission des lois'
            $table->string('organe_ref')->nullable(); // Référence vers organes_an ou null
            
            // Liens
            $table->string('url_source')->nullable(); // URL de la page source
            $table->string('url_video')->nullable(); // Lien vidéo/direct
            $table->string('url_dossier')->nullable(); // Dossier législatif associé
            
            // Affichage
            $table->string('couleur')->nullable(); // Couleur hex pour le calendrier
            $table->string('icone')->nullable(); // Emoji ou classe icône
            
            // Métadonnées iCal
            $table->string('ical_uid')->nullable(); // UID original du fichier iCal
            $table->timestamp('ical_last_modified')->nullable();
            
            // Statut
            $table->string('statut')->default('confirme'); // 'confirme', 'annule', 'reporte'
            
            $table->timestamps();
            
            // Index pour les requêtes fréquentes
            $table->index('source');
            $table->index('type');
            $table->index('date_debut');
            $table->index(['source', 'date_debut']);
            $table->index('instance_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_legislatifs');
    }
};

