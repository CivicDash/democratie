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
        Schema::create('territories_departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('Code INSEE département (ex: 75, 2A)');
            $table->string('name')->comment('Nom du département (ex: Paris)');
            $table->foreignId('region_id')->constrained('territories_regions')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('code');
            $table->index('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('territories_departments');
    }
};

