<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->text('file_path')->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->boolean('is_transitive')->default(false);
            $table->string('package_id')->nullable()->comment('e.g. pkg:npm/tar@7.5.7');
            $table->string('package_name')->nullable();
            $table->string('package_version')->nullable();
            $table->string('package_ecosystem')->nullable()->comment('npm, javascript, etc.');
            $table->text('description')->nullable();
            $table->string('license')->nullable();
            $table->string('md5')->nullable();
            $table->string('sha1')->nullable();
            $table->string('sha256')->nullable();
            $table->json('project_references')->nullable();
            $table->timestamps();

            $table->index(['scan_id', 'is_transitive']);
            $table->index('package_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};
