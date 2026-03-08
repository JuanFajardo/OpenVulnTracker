<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'description',
        'token_hash',
        'duration_days',
        'expires_at',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Token ya venció. */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Token vence dentro de 7 días (pero aún no ha vencido). */
    public function isExpiringSoon(): bool
    {
        return !$this->isExpired()
            && now()->diffInDays($this->expires_at, false) <= 7;
    }

    /**
     * Genera un nuevo par [plain, hash].
     *   plain → mostrar al usuario UNA sola vez
     *   hash  → almacenar en DB
     */
    public static function generate(): array
    {
        $plain = 'vt_' . bin2hex(random_bytes(32)); // "vt_" + 64 hex chars
        $hash  = hash('sha256', $plain);
        return ['plain' => $plain, 'hash' => $hash];
    }

    /** Busca el registro por el token plano (hashea y consulta). */
    public static function findByPlain(string $plain): ?self
    {
        return self::where('token_hash', hash('sha256', $plain))->first();
    }

    /** Devuelve clases Tailwind para colorear la fila según estado. */
    public function rowClass(): string
    {
        if ($this->isExpired()) {
            return 'bg-gray-100 opacity-60';
        }
        if ($this->isExpiringSoon()) {
            return 'bg-amber-50';
        }
        return '';
    }

    /** Badge de estado para la vista. */
    public function statusBadge(): array
    {
        if ($this->isExpired()) {
            return ['label' => 'Expirado', 'class' => 'bg-gray-200 text-gray-600'];
        }
        if ($this->isExpiringSoon()) {
            return ['label' => 'Por vencer', 'class' => 'bg-amber-100 text-amber-800'];
        }
        return ['label' => 'Activo', 'class' => 'bg-green-100 text-green-800'];
    }
}
