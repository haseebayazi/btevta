<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\OepController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Default Middleware: auth, throttle:60,1 (60 requests per minute)
| All routes automatically prefixed with /api
|
*/

// ========================================================================
// API v1 Routes
// Authentication: Required (web auth session)
// Throttle: 60 requests/minute
// ========================================================================

Route::prefix('v1')->name('v1.')->group(function () {

    // Candidate Search
    Route::get('/candidates/search', [CandidateController::class, 'apiSearch'])
        ->name('candidates.search');

    // Campus List
    Route::get('/campuses/list', [CampusController::class, 'apiList'])
        ->name('campuses.list');

    // OEP List
    Route::get('/oeps/list', [OepController::class, 'apiList'])
        ->name('oeps.list');

    // Trade List
    Route::get('/trades/list', [TradeController::class, 'apiList'])
        ->name('trades.list');

    // Batches by Campus
    Route::get('/batches/by-campus/{campus}', [BatchController::class, 'byCampus'])
        ->name('batches.by-campus');

    // User Notifications
    Route::get('/notifications', [UserController::class, 'notifications'])
        ->name('notifications');

    Route::post('/notifications/{notification}/mark-read', [UserController::class, 'markNotificationRead'])
        ->name('notifications.mark-read');
});
