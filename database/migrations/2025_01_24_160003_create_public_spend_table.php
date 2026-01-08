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
        Schema::create('public_spend', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->comment('Année fiscale');
            $table->enum('scope', ['national', 'region', 'dept']);
            $table->foreignId('region_id')->nullable()->constrained('territories_regions')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('territories_departments')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2)->comment('Montant dépensé en euros');
            $table->string('program', 255)->nullable()->comment('Programme spécifique');
            $table->string('source', 255)->nullable()->comment('Source des données');
            $table->timestamps();
            
            $table->index(['year', 'scope']);
            $table->index(['region_id', 'department_id']);
            $table->index('sector_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_spend');
    }
};

