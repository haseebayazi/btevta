<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\VisaProcessingController;
use App\Http\Controllers\DepartureController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DocumentArchiveController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\OepController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\TrainingClassController;

/*
|--------------------------------------------------------------------------
| COMPLETE WEB ROUTES - ALL ROUTES INCLUDED
|--------------------------------------------------------------------------
| This file includes:
| 1. All original routes (for backward compatibility)
| 2. All new routes from updated controllers
| 3. Notes on changes and additions
|--------------------------------------------------------------------------
*/

// Authentication Routes (UNCHANGED)
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard (UNCHANGED)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Dashboard Tabs (UNCHANGED)
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/candidates-listing', [DashboardController::class, 'candidatesListing'])->name('candidates-listing');
        Route::get('/screening', [DashboardController::class, 'screening'])->name('screening');
        Route::get('/registration', [DashboardController::class, 'registration'])->name('registration');
        Route::get('/training', [DashboardController::class, 'training'])->name('training');
        Route::get('/visa-processing', [DashboardController::class, 'visaProcessing'])->name('visa-processing');
        Route::get('/departure', [DashboardController::class, 'departure'])->name('departure');
        Route::get('/correspondence', [DashboardController::class, 'correspondence'])->name('correspondence');
        Route::get('/complaints', [DashboardController::class, 'complaints'])->name('complaints');
        Route::get('/document-archive', [DashboardController::class, 'documentArchive'])->name('document-archive');
        Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    });

    // Candidates Management (UNCHANGED)
    Route::resource('candidates', CandidateController::class);
    Route::prefix('candidates')->name('candidates.')->group(function () {
        Route::get('/{candidate}/profile', [CandidateController::class, 'profile'])->name('profile');
        Route::get('/{candidate}/timeline', [CandidateController::class, 'timeline'])->name('timeline');
        Route::post('/{candidate}/update-status', [CandidateController::class, 'updateStatus'])->name('update-status');
        Route::post('/{candidate}/assign-campus', [CandidateController::class, 'assignCampus'])->name('assign-campus');
        Route::post('/{candidate}/assign-oep', [CandidateController::class, 'assignOep'])->name('assign-oep');
        Route::post('/{candidate}/upload-photo', [CandidateController::class, 'uploadPhoto'])->name('upload-photo');
        Route::get('export', [CandidateController::class, 'export'])->name('export');
    });

    // Import/Export (UNCHANGED)
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/candidates', [ImportController::class, 'showCandidateImport'])->name('candidates.form');
        Route::post('/candidates', [ImportController::class, 'importCandidates'])->name('candidates.process');
        Route::get('/template/download', [ImportController::class, 'downloadTemplate'])->name('template.download');
    });

    // Screening (UNCHANGED)
    Route::resource('screening', ScreeningController::class)->except(['show']);
    Route::prefix('screening')->name('screening.')->group(function () {
        Route::get('/pending', [ScreeningController::class, 'pending'])->name('pending');
        Route::post('/{candidate}/call-log', [ScreeningController::class, 'logCall'])->name('log-call');
        Route::post('/{candidate}/screening-outcome', [ScreeningController::class, 'recordOutcome'])->name('outcome');
        Route::get('/export', [ScreeningController::class, 'export'])->name('export');
    });

    // Registration (UNCHANGED)
    Route::resource('registration', RegistrationController::class);
    Route::prefix('registration')->name('registration.')->group(function () {
        Route::post('/{candidate}/documents', [RegistrationController::class, 'uploadDocument'])->name('upload-document');
        Route::delete('/documents/{document}', [RegistrationController::class, 'deleteDocument'])->name('delete-document');
        Route::post('/{candidate}/next-of-kin', [RegistrationController::class, 'saveNextOfKin'])->name('next-of-kin');
        Route::post('/{candidate}/undertaking', [RegistrationController::class, 'saveUndertaking'])->name('undertaking');
        Route::post('/{candidate}/complete', [RegistrationController::class, 'completeRegistration'])->name('complete');
    });

    // ========================================================================
    // TRAINING ROUTES - UPDATED WITH NEW CONTROLLER
    // ========================================================================
    Route::resource('training', TrainingController::class);
    Route::prefix('training')->name('training.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/batches', [TrainingController::class, 'batches'])->name('batches');
        Route::post('/attendance', [TrainingController::class, 'markAttendance'])->name('attendance'); // Existing
        Route::post('/assessment', [TrainingController::class, 'recordAssessment'])->name('assessment'); // Existing
        Route::post('/{candidate}/certificate', [TrainingController::class, 'generateCertificate'])->name('certificate'); // Existing
        Route::get('/batch/{batch}/report', [TrainingController::class, 'batchReport'])->name('batch-report');
        
        // NEW ROUTES (From updated controller)
        Route::get('/attendance/form', [TrainingController::class, 'attendance'])->name('attendance-form'); // NEW
        Route::post('/{candidate}/mark-attendance', [TrainingController::class, 'markAttendance'])->name('mark-attendance'); // NEW - Individual
        Route::post('/attendance/bulk', [TrainingController::class, 'bulkAttendance'])->name('bulk-attendance'); // NEW - Bulk
        Route::get('/{candidate}/assessment', [TrainingController::class, 'assessment'])->name('assessment-view'); // NEW
        Route::post('/{candidate}/store-assessment', [TrainingController::class, 'storeAssessment'])->name('store-assessment'); // NEW
        Route::put('/assessment/{assessment}', [TrainingController::class, 'updateAssessment'])->name('update-assessment'); // NEW
        Route::get('/{candidate}/certificate/download', [TrainingController::class, 'downloadCertificate'])->name('download-certificate'); // NEW
        Route::post('/{candidate}/complete', [TrainingController::class, 'complete'])->name('complete'); // NEW
        Route::post('/reports/attendance', [TrainingController::class, 'attendanceReport'])->name('attendance-report'); // NEW
        Route::post('/reports/assessment', [TrainingController::class, 'assessmentReport'])->name('assessment-report'); // NEW
        Route::get('/batch/{batch}/performance', [TrainingController::class, 'batchPerformance'])->name('batch-performance'); // NEW
    });

    // ========================================================================
    // VISA PROCESSING ROUTES - UPDATED WITH NEW CONTROLLER
    // ========================================================================
    Route::resource('visa-processing', VisaProcessingController::class);
    Route::prefix('visa-processing')->name('visa-processing.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility - these may need controller method updates)
        Route::post('/{candidate}/interview', [VisaProcessingController::class, 'recordInterview'])->name('interview'); // Existing
        Route::post('/{candidate}/trade-test', [VisaProcessingController::class, 'recordTradeTest'])->name('trade-test'); // Existing
        Route::post('/{candidate}/takamol', [VisaProcessingController::class, 'recordTakamol'])->name('takamol'); // Existing
        Route::post('/{candidate}/medical', [VisaProcessingController::class, 'recordMedical'])->name('medical'); // Existing
        Route::post('/{candidate}/enumber', [VisaProcessingController::class, 'recordEnumber'])->name('enumber'); // Existing
        Route::post('/{candidate}/biometric', [VisaProcessingController::class, 'recordBiometric'])->name('biometric'); // Existing
        Route::post('/{candidate}/visa', [VisaProcessingController::class, 'recordVisa'])->name('visa'); // Existing
        Route::post('/{candidate}/ticket', [VisaProcessingController::class, 'uploadTicket'])->name('ticket'); // Existing
        Route::get('/timeline-report', [VisaProcessingController::class, 'timelineReport'])->name('timeline-report'); // Existing
        
        // NEW ROUTES (From updated controller)
        Route::post('/{candidate}/update-interview', [VisaProcessingController::class, 'updateInterview'])->name('update-interview'); // NEW
        Route::post('/{candidate}/update-trade-test', [VisaProcessingController::class, 'updateTradeTest'])->name('update-trade-test'); // NEW
        Route::post('/{candidate}/update-takamol', [VisaProcessingController::class, 'updateTakamol'])->name('update-takamol'); // NEW
        Route::post('/{candidate}/update-medical', [VisaProcessingController::class, 'updateMedical'])->name('update-medical'); // NEW
        Route::post('/{candidate}/update-biometric', [VisaProcessingController::class, 'updateBiometric'])->name('update-biometric'); // NEW
        Route::post('/{candidate}/update-visa', [VisaProcessingController::class, 'updateVisa'])->name('update-visa'); // NEW
        Route::get('/{candidate}/timeline', [VisaProcessingController::class, 'timeline'])->name('timeline'); // NEW
        Route::get('/reports/overdue', [VisaProcessingController::class, 'overdue'])->name('overdue'); // NEW
        Route::post('/{candidate}/complete', [VisaProcessingController::class, 'complete'])->name('complete'); // NEW
        Route::post('/reports/generate', [VisaProcessingController::class, 'report'])->name('report'); // NEW
    });

    // ========================================================================
    // DEPARTURE ROUTES - UPDATED WITH NEW CONTROLLER
    // ========================================================================
    Route::resource('departure', DepartureController::class);
    Route::prefix('departure')->name('departure.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::post('/{candidate}/briefing', [DepartureController::class, 'recordBriefing'])->name('briefing'); // Existing
        Route::post('/{candidate}/iqama', [DepartureController::class, 'recordIqama'])->name('iqama'); // Existing
        Route::post('/{candidate}/absher', [DepartureController::class, 'recordAbsher'])->name('absher'); // Existing
        Route::post('/{candidate}/qiwa', [DepartureController::class, 'recordQiwa'])->name('qiwa'); // Existing - DEPRECATED, use 'wps' instead
        Route::post('/{candidate}/salary', [DepartureController::class, 'recordSalary'])->name('salary'); // Existing - DEPRECATED, use 'first-salary' instead
        Route::post('/{candidate}/ninety-day-report', [DepartureController::class, 'submitNinetyDayReport'])->name('ninety-day-report'); // Existing
        Route::get('/pending-compliance', [DepartureController::class, 'pendingCompliance'])->name('pending-compliance'); // Existing
        
        // NEW ROUTES (From updated controller)
        Route::post('/{candidate}/record-departure', [DepartureController::class, 'recordDeparture'])->name('record-departure'); // NEW
        Route::post('/{candidate}/wps', [DepartureController::class, 'recordWps'])->name('wps'); // NEW (replaces qiwa)
        Route::post('/{candidate}/first-salary', [DepartureController::class, 'recordFirstSalary'])->name('first-salary'); // NEW (replaces salary)
        Route::post('/{candidate}/90-day-compliance', [DepartureController::class, 'record90DayCompliance'])->name('90-day-compliance'); // NEW
        Route::post('/{candidate}/issue', [DepartureController::class, 'reportIssue'])->name('report-issue'); // NEW
        Route::put('/issue/{issue}', [DepartureController::class, 'updateIssue'])->name('update-issue'); // NEW
        Route::get('/{candidate}/timeline', [DepartureController::class, 'timeline'])->name('timeline'); // NEW
        Route::post('/reports/compliance', [DepartureController::class, 'complianceReport'])->name('compliance-report'); // NEW
        Route::get('/tracking/90-days', [DepartureController::class, 'tracking90Days'])->name('tracking-90-days'); // NEW
        Route::get('/non-compliant', [DepartureController::class, 'nonCompliant'])->name('non-compliant'); // NEW
        Route::get('/active-issues', [DepartureController::class, 'activeIssues'])->name('active-issues'); // NEW
        Route::post('/{candidate}/returned', [DepartureController::class, 'markReturned'])->name('mark-returned'); // NEW
    });

    // Correspondence (UNCHANGED)
    Route::resource('correspondence', CorrespondenceController::class);
    Route::prefix('correspondence')->name('correspondence.')->group(function () {
        Route::get('/pending-reply', [CorrespondenceController::class, 'pendingReply'])->name('pending-reply');
        Route::post('/{correspondence}/mark-replied', [CorrespondenceController::class, 'markReplied'])->name('mark-replied');
        Route::get('/register', [CorrespondenceController::class, 'register'])->name('register');
    });

    // ========================================================================
    // COMPLAINT ROUTES - UPDATED WITH NEW CONTROLLER
    // ========================================================================
    Route::resource('complaints', ComplaintController::class);
    Route::prefix('complaints')->name('complaints.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/overdue', [ComplaintController::class, 'overdue'])->name('overdue'); // Existing
        Route::post('/{complaint}/assign', [ComplaintController::class, 'assign'])->name('assign'); // Existing
        Route::post('/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('resolve'); // Existing
        Route::post('/{complaint}/escalate', [ComplaintController::class, 'escalate'])->name('escalate'); // Existing
        Route::get('/statistics', [ComplaintController::class, 'statistics'])->name('statistics'); // Existing
        
        // NEW ROUTES (From updated controller)
        Route::post('/{complaint}/update', [ComplaintController::class, 'addUpdate'])->name('add-update'); // NEW
        Route::post('/{complaint}/evidence', [ComplaintController::class, 'addEvidence'])->name('add-evidence'); // NEW
        Route::post('/{complaint}/close', [ComplaintController::class, 'close'])->name('close'); // NEW
        Route::post('/{complaint}/reopen', [ComplaintController::class, 'reopen'])->name('reopen'); // NEW
        Route::get('/category/{category}', [ComplaintController::class, 'byCategory'])->name('by-category'); // NEW
        Route::get('/my/assignments', [ComplaintController::class, 'myAssignments'])->name('my-assignments'); // NEW
        Route::post('/reports/analytics', [ComplaintController::class, 'analytics'])->name('analytics'); // NEW
        Route::post('/reports/sla', [ComplaintController::class, 'slaReport'])->name('sla-report'); // NEW
        Route::post('/export', [ComplaintController::class, 'export'])->name('export'); // NEW
    });

    // ========================================================================
    // DOCUMENT ARCHIVE ROUTES - UPDATED WITH NEW CONTROLLER
    // ========================================================================
    Route::resource('document-archive', DocumentArchiveController::class)->except(['create', 'edit']); // Kept except
    Route::prefix('document-archive')->name('document-archive.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/expiring', [DocumentArchiveController::class, 'expiring'])->name('expiring'); // Existing
        Route::get('/search', [DocumentArchiveController::class, 'search'])->name('search'); // Existing
        Route::get('/{document}/download', [DocumentArchiveController::class, 'download'])->name('download'); // Existing
        Route::get('/{document}/versions', [DocumentArchiveController::class, 'versions'])->name('versions'); // Existing
        
        // NEW ROUTES (From updated controller)
        Route::get('/create', [DocumentArchiveController::class, 'create'])->name('create'); // NEW - Added back
        Route::get('/{document}/edit', [DocumentArchiveController::class, 'edit'])->name('edit'); // NEW - Added back
        Route::post('/{document}/version', [DocumentArchiveController::class, 'uploadVersion'])->name('upload-version'); // NEW
        Route::get('/{document}/view', [DocumentArchiveController::class, 'view'])->name('view'); // NEW
        Route::post('/{document}/restore-version', [DocumentArchiveController::class, 'restoreVersion'])->name('restore-version'); // NEW
        Route::get('/tracking/expired', [DocumentArchiveController::class, 'expired'])->name('expired'); // NEW
        Route::get('/candidate/{candidate}/documents', [DocumentArchiveController::class, 'candidateDocuments'])->name('candidate-documents'); // NEW
        Route::get('/{document}/access-logs', [DocumentArchiveController::class, 'accessLogs'])->name('access-logs'); // NEW
        Route::get('/reports/statistics', [DocumentArchiveController::class, 'statistics'])->name('statistics'); // NEW
        Route::post('/reports/generate', [DocumentArchiveController::class, 'report'])->name('report'); // NEW
        Route::post('/bulk/upload', [DocumentArchiveController::class, 'bulkUpload'])->name('bulk-upload'); // NEW
        Route::post('/{document}/archive', [DocumentArchiveController::class, 'archive'])->name('archive'); // NEW
        Route::post('/{document}/restore', [DocumentArchiveController::class, 'restore'])->name('restore'); // NEW
        Route::post('/reminders/send', [DocumentArchiveController::class, 'sendExpiryReminders'])->name('send-expiry-reminders'); // NEW
    });

    // Reports (UNCHANGED)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/candidate-profile/{candidate}', [ReportController::class, 'candidateProfile'])->name('candidate-profile');
        Route::get('/batch-summary/{batch}', [ReportController::class, 'batchSummary'])->name('batch-summary');
        Route::get('/campus-performance', [ReportController::class, 'campusPerformance'])->name('campus-performance');
        Route::get('/oep-performance', [ReportController::class, 'oepPerformance'])->name('oep-performance');
        Route::get('/visa-timeline', [ReportController::class, 'visaTimeline'])->name('visa-timeline');
        Route::get('/training-statistics', [ReportController::class, 'trainingStatistics'])->name('training-statistics');
        Route::get('/complaint-analysis', [ReportController::class, 'complaintAnalysis'])->name('complaint-analysis');
        Route::get('/custom-report', [ReportController::class, 'customReport'])->name('custom-report');
        Route::post('/generate-custom', [ReportController::class, 'generateCustomReport'])->name('generate-custom');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });

    // Admin Routes (UNCHANGED)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('campuses', CampusController::class);
        Route::post('campuses/{campus}/toggle-status', [CampusController::class, 'toggleStatus'])->name('campuses.toggle-status');
        Route::resource('oeps', OepController::class);
        Route::post('oeps/{oep}/toggle-status', [OepController::class, 'toggleStatus'])->name('oeps.toggle-status');
        Route::resource('trades', TradeController::class);
        Route::post('trades/{trade}/toggle-status', [TradeController::class, 'toggleStatus'])->name('trades.toggle-status');
        Route::resource('batches', BatchController::class);
        Route::post('batches/{batch}/change-status', [BatchController::class, 'changeStatus'])->name('batches.change-status');
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::get('/settings', [UserController::class, 'settings'])->name('settings');
        Route::post('/settings', [UserController::class, 'updateSettings'])->name('settings.update');
        Route::get('/audit-logs', [UserController::class, 'auditLogs'])->name('audit-logs');
    });

    // ========================================================================
    // INSTRUCTORS ROUTES - SECURITY FIX: Moved inside auth middleware
    // ========================================================================
    Route::resource('instructors', InstructorController::class);

    // ========================================================================
    // TRAINING CLASSES ROUTES - SECURITY FIX: Moved inside auth middleware
    // ========================================================================
    Route::resource('classes', TrainingClassController::class);
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::post('/{class}/assign-candidates', [TrainingClassController::class, 'assignCandidates'])->name('assign-candidates');
        Route::post('/{class}/remove-candidate/{candidate}', [TrainingClassController::class, 'removeCandidate'])->name('remove-candidate');
    });
});

// API Routes
Route::middleware(['auth', 'throttle:60,1'])->prefix('api')->name('api.')->group(function () {
    Route::get('/candidates/search', [CandidateController::class, 'apiSearch'])->name('candidates.search');
    Route::get('/campuses/list', [CampusController::class, 'apiList'])->name('campuses.list');
    Route::get('/oeps/list', [OepController::class, 'apiList'])->name('oeps.list');
    Route::get('/trades/list', [TradeController::class, 'apiList'])->name('trades.list');
    Route::get('/batches/by-campus/{campus}', [BatchController::class, 'byCampus'])->name('batches.by-campus');
    Route::get('/notifications', [UserController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{notification}/mark-read', [UserController::class, 'markNotificationRead'])->name('notifications.mark-read');
});

/*
|--------------------------------------------------------------------------
| ROUTE SUMMARY
|--------------------------------------------------------------------------
| Total Routes: ~180 (Original ~100 + New ~80)
|
| UPDATED MODULES (with new methods):
| - Training: 18 new/updated routes
| - Visa Processing: 16 new/updated routes
| - Departure: 19 new/updated routes
| - Complaints: 14 new/updated routes
| - Document Archive: 20 new/updated routes
|
| UNCHANGED MODULES:
| - Authentication
| - Dashboard
| - Candidates
| - Screening
| - Registration
| - Correspondence
| - Reports
| - Admin
| - API
|
| After updating, run:
| php artisan route:clear
| php artisan route:cache
| php artisan route:list
|--------------------------------------------------------------------------
*/