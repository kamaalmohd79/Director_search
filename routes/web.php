<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DirectorSearchController;

Route::get('/', fn () => redirect()->route('directors.search'));

Route::prefix('directors')->group(function () {
    Route::get('/search',  [DirectorSearchController::class, 'index'])->name('directors.search');      // form
    Route::post('/search', [DirectorSearchController::class, 'search'])->name('directors.search.run'); // results
});
