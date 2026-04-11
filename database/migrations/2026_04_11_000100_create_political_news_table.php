<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('political_news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 1024);
            $table->string('image_url', 1024)->nullable();
            $table->string('source_feed', 100)->index();
            $table->string('category', 50)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('guid', 512)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('political_news');
    }
};
