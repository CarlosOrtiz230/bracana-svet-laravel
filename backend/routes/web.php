<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\EducationalController;
use App\Http\Controllers\ZapScanController;

// Main scanner interface
Route::get('/', [ScanController::class, 'index'])->name('upload');

// Static analysis using Semgrep
Route::post('/scan/static', [ScanController::class, 'runStatic'])->name('scan.static');

// Dynamic analysis using Nikto/ZAP
Route::post('/scan/dynamic', [ScanController::class, 'runDynamic'])->name('scan.dynamic');

// Legacy scan route (optional if no longer used)
Route::post('/scan', [ScanController::class, 'scan'])->name('scan.run');

// Authentication routes
Auth::routes();

// Home route after login
Route::get('/home', [HomeController::class, 'index'])->name('home');


//metrics controller
Route::post('/metrics/nikto', [MetricsController::class, 'analyzeNiktoHtml'])->name('metrics.nikto');


Route::get('/metrics/test', function () {
    return view('metrics_nikto');
});


//ZAP SCANS store
Route::resource('zap-scans', ZapScanController::class)->only(['store', 'show']);


//educational one 

Route::post('/educational/nikto', [EducationalController::class, 'explainNiktoFindings'])->name('educational.nikto');
