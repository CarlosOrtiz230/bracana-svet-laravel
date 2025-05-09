<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\EducationalController;
use App\Http\Controllers\ZapScanController;
use App\Models\ZapScan;
use App\Models\NiktoScan;
use App\Models\SemgrepScan;
use App\Http\Controllers\SemgrepScanController;

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

Route::post('/educational-guidance', [EducationalController::class, 'generateGuidance']);
Route::get('/educate/{tool}/{id}', [ EducationalController::class, 'educateFromStorage'])->name('educate.fromStorage');
 

Route::get('/scan/raw/{tool}/{id}', function ($tool, $id) {
    $model = match ($tool) {
        'zap' => ZapScan::findOrFail($id),
        'nikto' => NiktoScan::findOrFail($id),
        'semgrep' => SemgrepScan::findOrFail($id),
        default => abort(404, 'Unknown scan tool'),
    };

    return response()->json(json_decode($model->raw_output, true));
})->name('scan.raw.json');



//History
Route::get('/scan/history', [ScanController::class, 'history'])->name('scan.history');
Route::get('/scan/zap/{id}', function ($id) {
    $scan = ZapScan::findOrFail($id);
    return view('results', ['results' => $scan->findings, 'tool' => 'zap', 'scan_id' => $scan->id]);
})->name('scan.results.zap');

// Route::get('/scan/nikto/{id}', function ($id) {
//     $scan =  NiktoScan::findOrFail($id);
//     return view('results', [
//         'results' => $scan->findings,
//         'tool' => 'nikto',
//         'scan_id' => $scan->id
//     ]);
// })->name('scan.results.nikto');
Route::get('/scan/nikto/{id}', [ScanController::class, 'showNiktoResults'])->name('scan.results.nikto');



//IA
Route::post('/educational/ai-comment', [EducationalController::class, 'aiComment'])->name('educational.aiComment');

//recovery path
Route::post('/scan/recover-stored', [ScanController::class, 'recoverStoredReports'])->name('scan.recoverStored');


//static analysis using semgrep 
 
Route::post('/scan/static/semgrep', [ScanController::class, 'runSemgrepScan'])->name('scan.semgrep');

Route::resource('semgrep-scans', SemgrepScanController::class)->only(['store', 'show']);

Route::get('/scan/semgrep/{id}', function ($id) {
    $scan = SemgrepScan::findOrFail($id);
    return view('results', [
        'results' => $scan->findings,
        'tool' => 'semgrep',
        'scan_id' => $scan->id,
    ]);
})->name('scan.results.semgrep');
