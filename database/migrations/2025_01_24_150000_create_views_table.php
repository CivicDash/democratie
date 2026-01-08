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
        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')
                ->comment('Utilisateur qui a vu (nullable pour anonymes)');
            $table->morphs('viewable'); // viewable_id + viewable_type
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            
            $table->index(['viewable_type', 'viewable_id', 'created_at'], 'idx_viewable_date');
            $table->index(['user_id', 'viewable_type', 'viewable_id'], 'idx_unique_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('views');
    }
};
