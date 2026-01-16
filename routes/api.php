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
use App\Http\Controllers\Api\CandidateApiController;
use App\Http\Controllers\Api\BatchApiController;
use App\Http\Controllers\Api\DepartureApiController;
use App\Http\Controllers\Api\VisaProcessApiController;
use App\Http\Controllers\Api\ScreeningApiController;
use App\Http\Controllers\Api\CorrespondenceApiController;
use App\Http\Controllers\Api\ComplaintApiController;
use App\Http\Controllers\Api\DocumentArchiveApiController;
use App\Http\Controllers\HealthController;

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
// Health Check Routes (No Authentication Required)
// Used by load balancers and monitoring systems
// ========================================================================

Route::get('/health', [HealthController::class, 'check'])->name('health.check');
Route::get('/health/detailed', [HealthController::class, 'detailed'])
    ->middleware(['auth:sanctum', 'role:admin,super_admin'])
    ->name('health.detailed');

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

    // User Notifications
    Route::get('/notifications', [UserController::class, 'notifications'])
        ->name('notifications');

    Route::post('/notifications/{notification}/mark-read', [UserController::class, 'markNotificationRead'])
        ->name('notifications.mark-read');

    // ========================================================================
    // CANDIDATE API ROUTES (AUDIT FIX: API-001)
    // ========================================================================

    Route::prefix('candidates')->name('candidates.')->group(function () {
        Route::get('/', [CandidateApiController::class, 'index'])->name('index');
        Route::get('/stats', [CandidateApiController::class, 'statistics'])->name('statistics');
        Route::get('/{id}', [CandidateApiController::class, 'show'])->name('show');
        Route::post('/', [CandidateApiController::class, 'store'])->name('store');
        Route::put('/{id}', [CandidateApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [CandidateApiController::class, 'destroy'])->name('destroy');
    });

    // ========================================================================
    // BATCH API ROUTES
    // ========================================================================

    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', [BatchApiController::class, 'index'])->name('index');
        Route::get('/active', [BatchApiController::class, 'active'])->name('active');
        Route::get('/by-campus/{campusId}', [BatchApiController::class, 'byCampus'])->name('by-campus');
        Route::post('/bulk-assign', [BatchApiController::class, 'bulkAssign'])->name('bulk-assign');
        Route::get('/{id}', [BatchApiController::class, 'show'])->name('show');
        Route::get('/{id}/statistics', [BatchApiController::class, 'statistics'])->name('statistics');
        Route::get('/{id}/candidates', [BatchApiController::class, 'candidates'])->name('candidates');
        Route::post('/', [BatchApiController::class, 'store'])->name('store');
        Route::put('/{id}', [BatchApiController::class, 'update'])->name('update');
        Route::post('/{id}/change-status', [BatchApiController::class, 'changeStatus'])->name('change-status');
        Route::delete('/{id}', [BatchApiController::class, 'destroy'])->name('destroy');
    });

    // ========================================================================
    // DEPARTURE API ROUTES (AUDIT FIX: API-001)
    // ========================================================================

    Route::prefix('departures')->name('departures.')->group(function () {
        Route::get('/', [DepartureApiController::class, 'index'])->name('index');
        Route::get('/stats', [DepartureApiController::class, 'statistics'])->name('statistics');
        Route::get('/candidate/{candidateId}', [DepartureApiController::class, 'byCandidate'])->name('by-candidate');
        Route::get('/{id}', [DepartureApiController::class, 'show'])->name('show');
        Route::post('/', [DepartureApiController::class, 'store'])->name('store');
        Route::put('/{id}', [DepartureApiController::class, 'update'])->name('update');
    });

    // ========================================================================
    // VISA PROCESS API ROUTES (AUDIT FIX: API-001)
    // ========================================================================

    Route::prefix('visa-processes')->name('visa-processes.')->group(function () {
        Route::get('/', [VisaProcessApiController::class, 'index'])->name('index');
        Route::get('/stats', [VisaProcessApiController::class, 'statistics'])->name('statistics');
        Route::get('/candidate/{candidateId}', [VisaProcessApiController::class, 'byCandidate'])->name('by-candidate');
        Route::get('/{id}', [VisaProcessApiController::class, 'show'])->name('show');
        Route::post('/', [VisaProcessApiController::class, 'store'])->name('store');
        Route::put('/{id}', [VisaProcessApiController::class, 'update'])->name('update');
    });

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

    // ========================================================================
    // SCREENING API ROUTES (PHASE 3)
    // ========================================================================

    Route::prefix('screenings')->name('screenings.')->group(function () {
        Route::get('/', [ScreeningApiController::class, 'index'])->name('index');
        Route::get('/stats', [ScreeningApiController::class, 'statistics'])->name('statistics');
        Route::get('/pending', [ScreeningApiController::class, 'pending'])->name('pending');
        Route::get('/candidate/{candidateId}', [ScreeningApiController::class, 'byCandidate'])->name('by-candidate');
        Route::get('/{id}', [ScreeningApiController::class, 'show'])->name('show');
        Route::post('/', [ScreeningApiController::class, 'store'])->name('store');
        Route::put('/{id}', [ScreeningApiController::class, 'update'])->name('update');
    });

    // ========================================================================
    // CORRESPONDENCE API ROUTES (PHASE 3)
    // ========================================================================

    Route::prefix('correspondence')->name('correspondence.')->group(function () {
        Route::get('/', [CorrespondenceApiController::class, 'index'])->name('index');
        Route::get('/stats', [CorrespondenceApiController::class, 'statistics'])->name('statistics');
        Route::get('/pending', [CorrespondenceApiController::class, 'pending'])->name('pending');
        Route::get('/{id}', [CorrespondenceApiController::class, 'show'])->name('show');
        Route::post('/', [CorrespondenceApiController::class, 'store'])->name('store');
        Route::put('/{id}', [CorrespondenceApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [CorrespondenceApiController::class, 'destroy'])->name('destroy');
    });

    // ========================================================================
    // COMPLAINTS API ROUTES (PHASE 3)
    // ========================================================================

    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/', [ComplaintApiController::class, 'index'])->name('index');
        Route::get('/stats', [ComplaintApiController::class, 'statistics'])->name('statistics');
        Route::get('/overdue', [ComplaintApiController::class, 'overdue'])->name('overdue');
        Route::get('/{id}', [ComplaintApiController::class, 'show'])->name('show');
        Route::post('/', [ComplaintApiController::class, 'store'])->name('store');
        Route::put('/{id}', [ComplaintApiController::class, 'update'])->name('update');
        Route::post('/{id}/assign', [ComplaintApiController::class, 'assign'])->name('assign');
        Route::post('/{id}/escalate', [ComplaintApiController::class, 'escalate'])->name('escalate');
        Route::post('/{id}/resolve', [ComplaintApiController::class, 'resolve'])->name('resolve');
    });

    // ========================================================================
    // DOCUMENT ARCHIVE API ROUTES (PHASE 3)
    // ========================================================================

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentArchiveApiController::class, 'index'])->name('index');
        Route::get('/stats', [DocumentArchiveApiController::class, 'statistics'])->name('statistics');
        Route::get('/search', [DocumentArchiveApiController::class, 'search'])->name('search');
        Route::get('/expiring', [DocumentArchiveApiController::class, 'expiring'])->name('expiring');
        Route::get('/expired', [DocumentArchiveApiController::class, 'expired'])->name('expired');
        Route::get('/candidate/{candidateId}', [DocumentArchiveApiController::class, 'byCandidate'])->name('by-candidate');
        Route::get('/{id}', [DocumentArchiveApiController::class, 'show'])->name('show');
        Route::get('/{id}/download', [DocumentArchiveApiController::class, 'download'])->name('download');
    });
});
