<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependency_id')->constrained()->cascadeOnDelete();
            $table->string('source')->nullable()->comment('NVD, NPM, etc.');
            $table->string('name')->comment('CVE-XXXX-XXXX or GHSA-xxx');
            $table->string('severity')->nullable()->comment('CRITICAL, HIGH, MEDIUM, LOW, INFO');
            $table->boolean('unscored')->default(false);

            // CVSS v2
            $table->decimal('cvssv2_score', 4, 1)->nullable();
            $table->string('cvssv2_severity')->nullable();
            $table->string('cvssv2_access_vector')->nullable();
            $table->string('cvssv2_access_complexity')->nullable();
            $table->decimal('cvssv2_exploitability_score', 4, 1)->nullable();
            $table->decimal('cvssv2_impact_score', 4, 1)->nullable();

            // CVSS v3
            $table->decimal('cvssv3_base_score', 4, 1)->nullable();
            $table->string('cvssv3_base_severity')->nullable();
            $table->string('cvssv3_attack_vector')->nullable();
            $table->string('cvssv3_attack_complexity')->nullable();
            $table->string('cvssv3_privileges_required')->nullable();
            $table->string('cvssv3_user_interaction')->nullable();
            $table->string('cvssv3_confidentiality_impact')->nullable();
            $table->string('cvssv3_integrity_impact')->nullable();
            $table->string('cvssv3_availability_impact')->nullable();
            $table->decimal('cvssv3_exploitability_score', 4, 1)->nullable();
            $table->decimal('cvssv3_impact_score', 4, 1)->nullable();

            $table->json('cwes')->nullable();
            $table->longText('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('references')->nullable();
            $table->json('vulnerable_software')->nullable();
            $table->timestamps();

            $table->index(['dependency_id', 'severity']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vulnerabilities');
    }
};
