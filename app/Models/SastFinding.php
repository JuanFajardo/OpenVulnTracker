<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SastFinding extends Model
{
    protected $fillable = [
        'sast_scan_id', 'check_id', 'file_path',
        'line_start', 'col_start', 'line_end', 'col_end',
        'severity', 'confidence', 'message', 'code_snippet',
        'category', 'cwe', 'owasp', 'references', 'fix',
    ];

    protected $casts = [
        'cwe'        => 'array',
        'owasp'      => 'array',
        'references' => 'array',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function scan(): BelongsTo
    {
        return $this->belongsTo(SastScan::class, 'sast_scan_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Clases Tailwind para el badge de severidad. */
    public function severityClass(): string
    {
        return match(strtoupper($this->severity)) {
            'ERROR'      => 'bg-red-100 text-red-800',
            'WARNING'    => 'bg-yellow-100 text-yellow-800',
            'INFO'       => 'bg-blue-100 text-blue-800',
            default      => 'bg-gray-100 text-gray-600',
        };
    }

    /** Clase para el borde lateral del panel expandido. */
    public function severityBorder(): string
    {
        return match(strtoupper($this->severity)) {
            'ERROR'   => 'border-red-400',
            'WARNING' => 'border-yellow-400',
            'INFO'    => 'border-blue-400',
            default   => 'border-gray-300',
        };
    }
}
