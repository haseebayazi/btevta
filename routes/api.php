<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\OepController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\RemittanceApiController;
use App\Http\Controllers\Api\RemittanceReportApiController;
use App\Http\Controllers\Api\RemittanceAlertApiController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\ApiTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| SECURITY: Using Sanctum for token-based API authentication
| Default Middleware: auth:sanctum, throttle:60,1 (60 requests per minute)
| All routes automatically prefixed with /api
|
*/

// ========================================================================
// API v1 Routes
// Authentication: Required (Sanctum token-based auth)
// Throttle: 60 requests/minute
// ========================================================================

// ========================================================================
// API Token Authentication Routes (Public)
// These routes allow users to authenticate and obtain API tokens
// ========================================================================

Route::prefix('v1/auth')->name('v1.auth.')->group(function () {
    Route::post('/token', [ApiTokenController::class, 'createToken'])->name('token.create');
});

Route::prefix('v1')->middleware(['auth:sanctum'])->name('v1.')->group(function () {

    // Token Management
    Route::get('/auth/tokens', [ApiTokenController::class, 'listTokens'])->name('auth.tokens.list');
    Route::delete('/auth/tokens/{tokenId}', [ApiTokenController::class, 'revokeToken'])->name('auth.tokens.revoke');
    Route::delete('/auth/tokens', [ApiTokenController::class, 'revokeAllTokens'])->name('auth.tokens.revoke-all');
    Route::get('/auth/user', [ApiTokenController::class, 'currentUser'])->name('auth.user');

    // Global Search
    Route::get('/global-search', [GlobalSearchController::class, 'search'])
        ->name('global-search');

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

    // ========================================================================
    // REMITTANCE API ROUTES
    // ========================================================================

    // Remittances CRUD
    Route::prefix('remittances')->name('remittances.')->group(function () {
        Route::get('/', [RemittanceApiController::class, 'index'])->name('index');
        Route::get('/{id}', [RemittanceApiController::class, 'show'])->name('show');
        Route::post('/', [RemittanceApiController::class, 'store'])->name('store');
        Route::put('/{id}', [RemittanceApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [RemittanceApiController::class, 'destroy'])->name('destroy');

        // Additional endpoints
        Route::get('/candidate/{candidateId}', [RemittanceApiController::class, 'byCandidate'])->name('by-candidate');
        Route::get('/search/query', [RemittanceApiController::class, 'search'])->name('search');
        Route::get('/stats/overview', [RemittanceApiController::class, 'statistics'])->name('statistics');
        Route::post('/{id}/verify', [RemittanceApiController::class, 'verify'])->name('verify');
    });

    // Remittance Reports
    Route::prefix('remittance/reports')->name('remittance.reports.')->group(function () {
        Route::get('/dashboard', [RemittanceReportApiController::class, 'dashboard'])->name('dashboard');
        Route::get('/monthly-trends', [RemittanceReportApiController::class, 'monthlyTrends'])->name('monthly-trends');
        Route::get('/purpose-analysis', [RemittanceReportApiController::class, 'purposeAnalysis'])->name('purpose-analysis');
        Route::get('/transfer-methods', [RemittanceReportApiController::class, 'transferMethods'])->name('transfer-methods');
        Route::get('/country-analysis', [RemittanceReportApiController::class, 'countryAnalysis'])->name('country-analysis');
        Route::get('/proof-compliance', [RemittanceReportApiController::class, 'proofCompliance'])->name('proof-compliance');
        Route::get('/beneficiary-report', [RemittanceReportApiController::class, 'beneficiaryReport'])->name('beneficiary-report');
        Route::get('/impact-analytics', [RemittanceReportApiController::class, 'impactAnalytics'])->name('impact-analytics');
        Route::get('/top-candidates', [RemittanceReportApiController::class, 'topCandidates'])->name('top-candidates');
    });

    // Remittance Alerts
    Route::prefix('remittance/alerts')->name('remittance.alerts.')->group(function () {
        Route::get('/', [RemittanceAlertApiController::class, 'index'])->name('index');
        Route::get('/{id}', [RemittanceAlertApiController::class, 'show'])->name('show');
        Route::get('/stats/overview', [RemittanceAlertApiController::class, 'statistics'])->name('statistics');
        Route::get('/stats/unread-count', [RemittanceAlertApiController::class, 'unreadCount'])->name('unread-count');
        Route::get('/candidate/{candidateId}', [RemittanceAlertApiController::class, 'byCandidate'])->name('by-candidate');
        Route::post('/{id}/read', [RemittanceAlertApiController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{id}/resolve', [RemittanceAlertApiController::class, 'resolve'])->name('resolve');
        Route::post('/{id}/dismiss', [RemittanceAlertApiController::class, 'dismiss'])->name('dismiss');
    });
});
