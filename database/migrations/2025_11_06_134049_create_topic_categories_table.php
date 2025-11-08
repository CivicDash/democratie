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
        Schema::create('topic_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable()->comment('Emoji ou classe d\'icône');
            $table->string('color', 7)->default('#6B7280')->comment('Couleur hex');
            $table->integer('order')->default(0)->comment('Ordre d\'affichage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'order']);
        });

        // Ajouter la colonne category_id à la table topics
        Schema::table('topics', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('topic_categories')->onDelete('set null');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        
        Schema::dropIfExists('topic_categories');
    }
};
