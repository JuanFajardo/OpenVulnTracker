<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── sast_scans ────────────────────────────────────────────────────────
        Schema::create('sast_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('branch', 100)->default('main');
            $table->string('semgrep_version', 30)->nullable(); // de "version" en el JSON
            $table->datetime('scan_date');                      // now() al momento de carga
            $table->datetime('uploaded_at')->nullable();
            $table->string('upload_source', 10)->default('cli'); // web|cli|api
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            // Totales por severidad Semgrep
            $table->unsignedInteger('total_error')->default(0);    // ERROR
            $table->unsignedInteger('total_warning')->default(0);  // WARNING
            $table->unsignedInteger('total_info')->default(0);     // INFO + EXPERIMENT
            $table->unsignedInteger('total_findings')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'branch', 'scan_date']);
        });

        // ── sast_findings ─────────────────────────────────────────────────────
        Schema::create('sast_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sast_scan_id')->constrained('sast_scans')->cascadeOnDelete();

            // Regla y ubicación
            $table->string('check_id');                // ej. "javascript.lang.security.audit..."
            $table->string('file_path');               // path del archivo afectado
            $table->unsignedInteger('line_start')->nullable();
            $table->unsignedInteger('col_start')->nullable();
            $table->unsignedInteger('line_end')->nullable();
            $table->unsignedInteger('col_end')->nullable();

            // Severidad (Semgrep: ERROR|WARNING|INFO|EXPERIMENT)
            $table->string('severity', 20);
            $table->string('confidence', 10)->nullable(); // HIGH|MEDIUM|LOW

            // Contenido del hallazgo
            $table->text('message');                   // extra.message
            $table->text('code_snippet')->nullable();  // extra.lines

            // Metadatos de la regla
            $table->string('category', 50)->nullable(); // metadata.category (ej. "security")
            $table->json('cwe')->nullable();             // metadata.cwe  ej. ["CWE-89: SQL Injection"]
            $table->json('owasp')->nullable();           // metadata.owasp ej. ["A03:2021 - Injection"]
            $table->json('references')->nullable();      // metadata.references
            $table->text('fix')->nullable();             // metadata.fix

            $table->timestamps();

            $table->index(['sast_scan_id', 'severity']);
            $table->index('check_id');
            $table->index('file_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sast_findings');
        Schema::dropIfExists('sast_scans');
    }
};
