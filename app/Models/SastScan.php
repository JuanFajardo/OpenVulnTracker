<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SastScan extends Model
{
    protected $fillable = [
        'project_id', 'branch', 'semgrep_version', 'scan_date',
        'uploaded_at', 'upload_source', 'uploaded_by',
        'total_error', 'total_warning', 'total_info', 'total_findings',
    ];

    protected $casts = [
        'scan_date'   => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    // ─── Constants ────────────────────────────────────────────────────────────

    const SOURCES = [
        'web' => 'Interfaz Web',
        'cli' => 'Línea de Comandos',
        'api' => 'API',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SastFinding::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->upload_source] ?? $this->upload_source;
    }

    /** Severidad dominante para mostrar badge en listados. */
    public function dominantSeverity(): string
    {
        if ($this->total_error > 0)   return 'ERROR';
        if ($this->total_warning > 0) return 'WARNING';
        if ($this->total_info > 0)    return 'INFO';
        return 'INFO';
    }
}
