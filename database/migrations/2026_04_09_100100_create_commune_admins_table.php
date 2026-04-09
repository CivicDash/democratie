<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commune_page_id')->constrained('commune_pages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('delegue');

            // Permissions granulaires
            $table->boolean('peut_publier_actus')->default(false);
            $table->boolean('peut_gerer_evenements')->default(false);
            $table->boolean('peut_envoyer_notifications')->default(false);
            $table->boolean('peut_modifier_page')->default(false);
            $table->boolean('peut_deleguer')->default(false);

            // Délégation
            $table->foreignId('delegue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expire_le')->nullable();

            $table->timestamps();

            $table->unique(['commune_page_id', 'user_id']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE commune_admins ADD CONSTRAINT commune_admins_role_check CHECK (role IN ('maire', 'adjoint', 'delegue', 'communication'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_admins');
    }
};
