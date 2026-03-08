<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scan extends Model
{
    protected $fillable = [
        'project_id',
        'branch',
        'engine_version',
        'report_date',
        'uploaded_at',
        'upload_source',
        'uploaded_by',
        'scan_file_path',
        'total_critical',
        'total_high',
        'total_medium',
        'total_low',
        'total_info',
        'total_dependencies',
        'total_vulnerable_dependencies',
    ];

    protected $casts = [
        'report_date' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    public const SOURCES = [
        'web' => 'Interfaz Web',
        'cli' => 'Línea de Comandos',
        'api' => 'API',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(Dependency::class);
    }

    public function directDependencies(): HasMany
    {
        return $this->hasMany(Dependency::class)->where('is_transitive', false);
    }

    public function transitiveDependencies(): HasMany
    {
        return $this->hasMany(Dependency::class)->where('is_transitive', true);
    }

    public function getSourceLabelAttribute(): string
    {
        return static::SOURCES[$this->upload_source] ?? ucfirst($this->upload_source ?? 'CLI');
    }
}
