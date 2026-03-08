<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * En MySQL, la primera columna TIMESTAMP NOT NULL sin DEFAULT recibe
     * automáticamente "ON UPDATE CURRENT_TIMESTAMP", lo que resetea expires_at
     * a now() en cada UPDATE (por ejemplo, al actualizar last_used_at en el
     * middleware). Cambiar a DATETIME evita completamente este comportamiento.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE api_tokens MODIFY COLUMN expires_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE api_tokens MODIFY COLUMN last_used_at DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE api_tokens MODIFY COLUMN expires_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE api_tokens MODIFY COLUMN last_used_at TIMESTAMP NULL');
    }
};
