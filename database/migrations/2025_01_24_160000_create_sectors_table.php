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
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Code unique (ex: EDU, HEALTH)');
            $table->string('name')->comment('Nom (ex: Éducation, Santé)');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable()->comment('Nom d\'icône (ex: graduation-cap)');
            $table->string('color', 7)->nullable()->comment('Couleur hex (ex: #0055a4)');
            $table->decimal('min_percent', 5, 2)->default(0)->comment('% minimum allouable');
            $table->decimal('max_percent', 5, 2)->default(100)->comment('% maximum allouable');
            $table->integer('display_order')->default(0)->comment('Ordre d\'affichage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};

