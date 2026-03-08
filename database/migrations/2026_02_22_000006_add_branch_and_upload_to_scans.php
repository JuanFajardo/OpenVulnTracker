<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Branch from project name (format: "Proyecto_RAMA")
            $table->string('branch')->default('main')->after('project_id');

            // When the file was actually uploaded/imported into VulnTracker
            $table->timestamp('uploaded_at')->nullable()->after('report_date');

            // How the scan was imported
            $table->string('upload_source')->default('cli')
                  ->comment('web | cli | api')->after('uploaded_at');

            // Who imported it (null if done via CLI without auth)
            $table->foreignId('uploaded_by')->nullable()
                  ->constrained('users')->nullOnDelete()->after('upload_source');

            $table->index(['project_id', 'branch', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['branch', 'uploaded_at', 'upload_source', 'uploaded_by']);
        });
    }
};
