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
        Schema::create('public_revenue', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->comment('Année fiscale');
            $table->enum('scope', ['national', 'region', 'dept']);
            $table->foreignId('region_id')->nullable()->constrained('territories_regions')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('territories_departments')->onDelete('cascade');
            $table->string('category', 100)->comment('Catégorie (ex: TVA, IRPP)');
            $table->decimal('amount', 15, 2)->comment('Montant en euros');
            $table->string('source', 255)->nullable()->comment('Source des données (ex: INSEE, DGFiP)');
            $table->timestamps();
            
            $table->index(['year', 'scope']);
            $table->index(['region_id', 'department_id']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_revenue');
    }
};

