<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commune_galerie_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commune_page_id')->constrained('commune_pages')->cascadeOnDelete();
            $table->string('image_path')->nullable();
            $table->string('legende')->nullable();
            $table->string('credit')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->string('source', 20)->default('upload');
            $table->string('wikimedia_url')->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['commune_page_id', 'ordre']);
        });

        DB::statement("ALTER TABLE commune_galerie_images ADD CONSTRAINT commune_galerie_images_source_check CHECK (source IN ('upload', 'wikimedia'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('commune_galerie_images');
    }
};
