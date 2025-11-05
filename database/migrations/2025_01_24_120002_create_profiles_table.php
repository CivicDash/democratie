<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('display_name')->comment('Pseudonyme aléatoire (ex: Citoyen123) ou nom réel pour personnalités publiques');
            $table->string('citizen_ref_hash')->nullable()->unique()->comment('Hash du numéro de sécu + PEPPER (anonyme) - NULL pour personnalités publiques');
            $table->enum('scope', ['national', 'region', 'dept'])->default('national')
                ->comment('Scope de participation');
            $table->foreignId('region_id')->nullable()->constrained('territories_regions')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('territories_departments')->onDelete('set null');
            $table->boolean('is_verified')->default(false)->comment('Identité vérifiée (FranceConnect+)');
            $table->timestamp('verified_at')->nullable();
            $table->text('bio')->nullable()->comment('Biographie (pour députés, personnalités publiques)');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('citizen_ref_hash');
            $table->index(['scope', 'region_id', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};

