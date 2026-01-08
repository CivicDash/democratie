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
        Schema::create('territories_regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Code INSEE région (ex: 11, 93)');
            $table->string('name')->comment('Nom de la région (ex: Île-de-France)');
            $table->timestamps();
            
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('territories_regions');
    }
};

