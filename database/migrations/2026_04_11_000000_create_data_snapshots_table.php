<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->index();
            $table->jsonb('counters');
            $table->string('checksum', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_snapshots');
    }
};
