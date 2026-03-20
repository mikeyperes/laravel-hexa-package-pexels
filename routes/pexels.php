<?php

use Illuminate\Support\Facades\Route;
use hexa_package_pexels\Http\Controllers\PexelsController;

/*
|--------------------------------------------------------------------------
| Pexels Package Routes
|--------------------------------------------------------------------------
| All routes behind core's auth + middleware stack.
| The service provider handles registration.
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'locked', 'system_lock', 'two_factor', 'role'])->group(function () {

    // ── Raw dev page ──
    Route::get('/raw-pexels', [PexelsController::class, 'raw'])->name('pexels.index');

    // ── AJAX endpoints ──
    Route::post('/pexels/search', [PexelsController::class, 'search'])->name('pexels.search');

});
