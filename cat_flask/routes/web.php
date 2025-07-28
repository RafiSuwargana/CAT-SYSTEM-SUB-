<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HybridCATController;
use App\Http\Controllers\TestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// CAT System Web Routes - Hybrid Mode Only
Route::get('/cat/hybrid', function () {
    return view('cat.hybrid');
})->name('cat.hybrid');

// API Routes for CAT (Hybrid - Flask + Laravel)
Route::prefix('api')->group(function () {
    // Test endpoints
    Route::get('/test', [TestController::class, 'simpleTest'])->name('api.test');
    Route::get('/test-db', [TestController::class, 'testDatabase'])->name('api.test-db');
    
    // Main CAT endpoints (menggunakan HybridCATController)
    Route::post('/start-test', [HybridCATController::class, 'startTest'])->name('api.start-test');
    Route::post('/submit-response', [HybridCATController::class, 'submitResponse'])->name('api.submit-response');
    Route::get('/session-history/{sessionId}', [HybridCATController::class, 'getSessionHistory'])->name('api.session-history');
    
    // Flask API management endpoints
    Route::get('/flask-health', [HybridCATController::class, 'getFlaskApiHealth'])->name('api.flask-health');
    Route::post('/switch-api', [HybridCATController::class, 'switchApiSource'])->name('api.switch-api');
    Route::get('/api-info', [HybridCATController::class, 'getApiInfo'])->name('api.api-info');
    
    // Flask API testing endpoints
    Route::prefix('test')->group(function () {
        Route::post('/estimate-theta', [HybridCATController::class, 'testEstimateTheta'])->name('api.test.estimate-theta');
        Route::post('/next-item', [HybridCATController::class, 'testNextItem'])->name('api.test.next-item');
        Route::post('/calculate-score', [HybridCATController::class, 'testCalculateScore'])->name('api.test.calculate-score');
        Route::post('/stopping-criteria', [HybridCATController::class, 'testStoppingCriteria'])->name('api.test.stopping-criteria');
        Route::get('/debug-stopping', [HybridCATController::class, 'debugStoppingCriteria'])->name('api.test.debug-stopping');
    });
});

// Note: CATController dan CATService masih dipertahankan karena digunakan 
// oleh HybridCATService sebagai fallback ketika Flask API tidak tersedia
