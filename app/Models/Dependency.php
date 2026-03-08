<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependency extends Model
{
    protected $fillable = [
        'scan_id',
        'file_name',
        'file_path',
        'is_virtual',
        'is_transitive',
        'package_id',
        'package_name',
        'package_version',
        'package_ecosystem',
        'description',
        'license',
        'md5',
        'sha1',
        'sha256',
        'project_references',
    ];

    protected $casts = [
        'is_virtual' => 'boolean',
        'is_transitive' => 'boolean',
        'project_references' => 'array',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function vulnerabilities(): HasMany
    {
        return $this->hasMany(Vulnerability::class);
    }

    public function getMaxSeverityAttribute(): string
    {
        $order = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3, 'INFO' => 4];
        $max = 'INFO';
        foreach ($this->vulnerabilities as $vuln) {
            $sev = strtoupper($vuln->severity ?? 'INFO');
            if (isset($order[$sev]) && $order[$sev] < $order[$max]) {
                $max = $sev;
            }
        }
        return $max;
    }
}
