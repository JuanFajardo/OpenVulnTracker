<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\SastScan;

class Project extends Model
{
    protected $fillable = ['name', 'name_hash', 'description'];

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function trivyScans(): HasMany
    {
        return $this->hasMany(TrivyScan::class);
    }

    public function sastScans(): HasMany
    {
        return $this->hasMany(SastScan::class);
    }

    public function latestTrivyScanForBranch(string $branch): ?TrivyScan
    {
        return $this->trivyScans()
            ->where('branch', $branch)
            ->orderByDesc('scan_date')
            ->first();
    }

    public function latestSastScanForBranch(string $branch): ?SastScan
    {
        return $this->sastScans()
            ->where('branch', $branch)
            ->orderByDesc('scan_date')
            ->first();
    }

    /** All distinct branches across SCA, Trivy and SAST scans. */
    public function allBranches(): array
    {
        $sca   = $this->scans()->distinct()->pluck('branch');
        $trivy = $this->trivyScans()->distinct()->pluck('branch');
        $sast  = $this->sastScans()->distinct()->pluck('branch');
        return $sca->merge($trivy)->merge($sast)->unique()->sort()->values()->toArray();
    }

    /**
     * All distinct branches this project has been scanned on.
     */
    public function branches(): array
    {
        return $this->scans()
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->toArray();
    }

    /**
     * Latest SCA scan across ALL branches (for dashboard summary).
     */
    public function latestScan(): HasOne
    {
        return $this->hasOne(Scan::class)->latestOfMany('report_date');
    }

    /**
     * Latest Trivy scan across ALL branches (for dashboard summary).
     */
    public function latestTrivyScan(): HasOne
    {
        return $this->hasOne(TrivyScan::class)->latestOfMany('scan_date');
    }

    /**
     * Latest SAST scan across ALL branches (for dashboard summary).
     */
    public function latestSastScan(): HasOne
    {
        return $this->hasOne(SastScan::class)->latestOfMany('scan_date');
    }

    /**
     * Latest scan for a specific branch.
     */
    public function latestScanForBranch(string $branch): ?Scan
    {
        return $this->scans()
            ->where('branch', $branch)
            ->orderByDesc('report_date')
            ->first();
    }

    /**
     * Parse "Nombre-Proyecto_RAMA" → ['Nombre-Proyecto', 'RAMA']
     * If no underscore, returns [fullName, 'main'].
     */
    public static function parseName(string $fullName): array
    {
        $pos = strrpos($fullName, '_');
        if ($pos === false || $pos === 0) {
            return [$fullName, 'main'];
        }
        $name   = substr($fullName, 0, $pos);
        $branch = substr($fullName, $pos + 1);
        return [$name ?: $fullName, $branch ?: 'main'];
    }
}
