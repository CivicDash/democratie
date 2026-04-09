<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('commune_code_insee', 5);

            // Préférences de notification
            $table->boolean('notif_actus')->default(true);
            $table->boolean('notif_evenements')->default(true);
            $table->boolean('notif_forum')->default(false);
            $table->boolean('notif_email')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'commune_code_insee']);
            $table->index('commune_code_insee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_abonnements');
    }
};
