<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrivyScan extends Model
{
    protected $fillable = [
        'project_id', 'branch', 'artifact_name', 'artifact_type',
        'os_family', 'os_name', 'schema_version', 'scan_date',
        'uploaded_at', 'upload_source', 'uploaded_by',
        'total_critical', 'total_high', 'total_medium',
        'total_low', 'total_unknown', 'total_vulnerabilities',
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

    public function vulnerabilities(): HasMany
    {
        return $this->hasMany(TrivyVulnerability::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->upload_source] ?? $this->upload_source;
    }

    /** Clases de color para badge de severidad dominante. */
    public function dominantSeverity(): string
    {
        if ($this->total_critical > 0) return 'CRITICAL';
        if ($this->total_high > 0)     return 'HIGH';
        if ($this->total_medium > 0)   return 'MEDIUM';
        if ($this->total_low > 0)      return 'LOW';
        return 'UNKNOWN';
    }
}
