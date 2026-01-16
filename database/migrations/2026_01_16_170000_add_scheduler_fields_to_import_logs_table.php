<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->string('triggered_by', 20)->default('manual')->after('user_id');
            $table->string('schedule_expression')->nullable()->after('triggered_by');
            $table->unsignedInteger('exit_code')->nullable()->after('duration_seconds');
            $table->text('output_tail')->nullable()->after('error_details');
        });
    }

    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropColumn([
                'triggered_by',
                'schedule_expression',
                'exit_code',
                'output_tail',
            ]);
        });
    }
};
