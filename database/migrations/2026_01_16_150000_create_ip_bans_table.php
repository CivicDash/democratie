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
        Schema::create('ip_bans', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45);
            $table->string('scope', 30);
            $table->string('ban_key');
            $table->string('abuse_key')->nullable();
            $table->unsignedInteger('abuse_count')->nullable();
            $table->unsignedInteger('ban_seconds')->default(0);
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('unbanned_at')->nullable();
            $table->foreignId('unbanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unban_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['ip', 'scope']);
            $table->index('expires_at');
            $table->index('unbanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_bans');
    }
};
