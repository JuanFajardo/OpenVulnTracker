<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — autenticadas con token Bearer (ApiTokenAuth middleware)
|--------------------------------------------------------------------------
|
| Uso con curl:
|
|   curl -X POST \
|     -H "Authorization: Bearer vt_xxxx..." \
|     -F "file=@dependency-check-report.json" \
|     -F "branch=main" \
|     http://localhost/OpenAppSec/vulntracker/public/api/import
|
*/

Route::middleware('api_token_auth')->group(function () {
    Route::post('/import', [ImportController::class, 'store'])->name('import');
});
