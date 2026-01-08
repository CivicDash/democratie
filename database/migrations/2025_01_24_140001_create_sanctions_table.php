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
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('report_id')->nullable()->constrained()->onDelete('set null')
                ->comment('Signalement à l\'origine (si applicable)');
            $table->enum('type', ['warning', 'mute', 'ban'])->comment('Type de sanction');
            $table->text('reason');
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('NULL = permanent');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index(['user_id', 'is_active', 'expires_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};

