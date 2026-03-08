<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── trivy_scans ──────────────────────────────────────────────────────
        Schema::create('trivy_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('branch', 100)->default('main');
            $table->string('artifact_name')->nullable();   // ej. "appsecco/dvna:sqlite"
            $table->string('artifact_type', 50)->nullable(); // container_image, filesystem…
            $table->string('os_family', 50)->nullable();   // debian, alpine…
            $table->string('os_name', 50)->nullable();     // 8.11, 3.18…
            $table->tinyInteger('schema_version')->nullable();
            $table->datetime('scan_date');                 // now() al momento de carga
            $table->datetime('uploaded_at')->nullable();
            $table->string('upload_source', 10)->default('cli'); // web|cli|api
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            // Totales por severidad
            $table->unsignedInteger('total_critical')->default(0);
            $table->unsignedInteger('total_high')->default(0);
            $table->unsignedInteger('total_medium')->default(0);
            $table->unsignedInteger('total_low')->default(0);
            $table->unsignedInteger('total_unknown')->default(0);
            $table->unsignedInteger('total_vulnerabilities')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'branch', 'scan_date']);
        });

        // ── trivy_vulnerabilities ────────────────────────────────────────────
        Schema::create('trivy_vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trivy_scan_id')->constrained('trivy_scans')->cascadeOnDelete();

            // Target (resultado de Trivy)
            $table->string('target')->nullable();          // "appsecco/dvna:sqlite (debian 8.11)"
            $table->string('target_class', 30)->nullable(); // os-pkgs, lang-pkgs
            $table->string('target_type', 50)->nullable();  // debian, node-pkg, npm…

            // Paquete afectado
            $table->string('pkg_name');
            $table->string('pkg_version')->nullable();     // InstalledVersion
            $table->string('pkg_path')->nullable();        // ruta en el sistema de archivos
            $table->string('fixed_version')->nullable();
            $table->string('status', 30)->nullable();      // fixed, affected…

            // Vulnerabilidad
            $table->string('vuln_id', 30);                 // CVE-xxxx / GHSA-xxxx
            $table->string('severity', 10);                // CRITICAL/HIGH/MEDIUM/LOW/UNKNOWN
            $table->string('severity_source', 30)->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('primary_url')->nullable();

            // CVSS (fuente nvd preferida, luego primera disponible)
            $table->string('cvss_source', 30)->nullable();
            $table->decimal('cvss_v2_score', 4, 1)->nullable();
            $table->string('cvss_v2_vector')->nullable();
            $table->decimal('cvss_v3_score', 4, 1)->nullable();
            $table->string('cvss_v3_vector')->nullable();

            // Listas
            $table->json('cwes')->nullable();              // ["CWE-190", ...]
            $table->json('references')->nullable();        // ["https://...", ...]

            $table->datetime('published_date')->nullable();
            $table->datetime('last_modified_date')->nullable();
            $table->timestamps();

            $table->index(['trivy_scan_id', 'severity']);
            $table->index('vuln_id');
            $table->index('pkg_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trivy_vulnerabilities');
        Schema::dropIfExists('trivy_scans');
    }
};
