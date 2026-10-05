<?php

use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

// Stateless endpoints: no database session or authentication required by crawlers.
Route::get('/robots.txt', [MetadataController::class, 'robots'])->name('metadata.robots');
Route::get('/sitemap.xml', [MetadataController::class, 'sitemap'])->name('metadata.sitemap');
Route::get('/manifest.webmanifest', [MetadataController::class, 'manifest'])->name('metadata.manifest');
