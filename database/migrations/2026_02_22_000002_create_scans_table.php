<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('engine_version')->nullable();
            $table->timestamp('report_date')->nullable();
            $table->string('scan_file_path')->nullable();
            $table->unsignedInteger('total_critical')->default(0);
            $table->unsignedInteger('total_high')->default(0);
            $table->unsignedInteger('total_medium')->default(0);
            $table->unsignedInteger('total_low')->default(0);
            $table->unsignedInteger('total_info')->default(0);
            $table->unsignedInteger('total_dependencies')->default(0);
            $table->unsignedInteger('total_vulnerable_dependencies')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
