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
        Schema::create('reunions_an', function (Blueprint $table) {
            $table->string('uid', 30)->primary()->comment('UID réunion (ex: RUANR5L17S2025IDS29165)');
            $table->string('organe_ref', 20)->nullable()->index();
            $table->integer('legislature')->nullable()->index();
            $table->date('date_reunion')->nullable()->index();
            $table->string('type_reunion', 50)->nullable();
            $table->json('details')->nullable()->comment('Ordre du jour, présences, etc.');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('organe_ref')->references('uid')->on('organes_an')->onDelete('set null');
            
            // Index composites
            $table->index(['legislature', 'date_reunion']);
            $table->index(['organe_ref', 'date_reunion']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reunions_an');
    }
};

