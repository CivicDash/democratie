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
        Schema::create('user_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sector_id')->constrained()->onDelete('cascade');
            $table->decimal('percent', 5, 2)->comment('% alloué au secteur');
            $table->timestamps();
            
            // Un user = une allocation par secteur
            $table->unique(['user_id', 'sector_id']);
            $table->index('user_id');
            $table->index('sector_id');
            
            // Constraint: percent entre min_percent et max_percent du sector
            // Sera validé en PHP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_allocations');
    }
};

