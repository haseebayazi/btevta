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

    // ========================================================================
    // CANDIDATES MANAGEMENT
    // Throttle: Standard 60/min, Export 5/min, Upload 30/min
    // ========================================================================
    Route::resource('candidates', CandidateController::class);
    Route::prefix('candidates')->name('candidates.')->group(function () {
        Route::get('/{candidate}/profile', [CandidateController::class, 'profile'])->name('profile');
        Route::get('/{candidate}/timeline', [CandidateController::class, 'timeline'])->name('timeline');
        Route::post('/{candidate}/update-status', [CandidateController::class, 'updateStatus'])->name('update-status');
        Route::post('/{candidate}/assign-campus', [CandidateController::class, 'assignCampus'])->name('assign-campus');
        Route::post('/{candidate}/assign-oep', [CandidateController::class, 'assignOep'])->name('assign-oep');

        // THROTTLE FIX: Upload limited to 30/min to prevent abuse
        Route::post('/{candidate}/upload-photo', [CandidateController::class, 'uploadPhoto'])
            ->middleware('throttle:30,1')->name('upload-photo');

        // THROTTLE FIX: Export limited to 5/min (resource intensive)
        Route::get('export', [CandidateController::class, 'export'])
            ->middleware('throttle:5,1')->name('export');
    });

    // ========================================================================
    // IMPORT/EXPORT
    // Throttle: View 60/min, Import 5/min (database intensive)
    // ========================================================================
    Route::prefix('import')->name('import.')->group(function () {
        Route::get('/candidates', [ImportController::class, 'showCandidateImport'])->name('candidates.form');

        // THROTTLE FIX: Import limited to 5/min (database intensive)
        Route::post('/candidates', [ImportController::class, 'importCandidates'])
            ->middleware('throttle:5,1')->name('candidates.process');

        Route::get('/template/download', [ImportController::class, 'downloadTemplate'])->name('template.download');
    });

    // ========================================================================
    // SCREENING
    // Throttle: Standard 60/min, Export 5/min
    // ========================================================================
    Route::resource('screening', ScreeningController::class)->except(['show']);
    Route::prefix('screening')->name('screening.')->group(function () {
        Route::get('/pending', [ScreeningController::class, 'pending'])->name('pending');
        Route::post('/{candidate}/call-log', [ScreeningController::class, 'logCall'])->name('log-call');
        Route::post('/{candidate}/screening-outcome', [ScreeningController::class, 'recordOutcome'])->name('outcome');

        // THROTTLE FIX: Export limited to 5/min (resource intensive)
        Route::get('/export', [ScreeningController::class, 'export'])
            ->middleware('throttle:5,1')->name('export');
    });

    // ========================================================================
    // REGISTRATION
    // Throttle: Document upload 30/min to prevent storage abuse
    // ========================================================================
    Route::resource('registration', RegistrationController::class);
    Route::prefix('registration')->name('registration.')->group(function () {
        // THROTTLE FIX: Upload limited to 30/min (storage abuse prevention)
        Route::post('/{candidate}/documents', [RegistrationController::class, 'uploadDocument'])
            ->middleware('throttle:30,1')->name('upload-document');

        Route::delete('/documents/{document}', [RegistrationController::class, 'deleteDocument'])->name('delete-document');
        Route::post('/{candidate}/next-of-kin', [RegistrationController::class, 'saveNextOfKin'])->name('next-of-kin');
        Route::post('/{candidate}/undertaking', [RegistrationController::class, 'saveUndertaking'])->name('undertaking');
        Route::post('/{candidate}/complete', [RegistrationController::class, 'completeRegistration'])->name('complete');
    });

    // ========================================================================
    // TRAINING ROUTES
    // Throttle: Standard 60/min, Bulk operations 30/min, Reports 5/min
    // ========================================================================
    Route::resource('training', TrainingController::class);
    Route::prefix('training')->name('training.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/batches', [TrainingController::class, 'batches'])->name('batches');
        Route::post('/attendance', [TrainingController::class, 'markAttendance'])->name('attendance');
        Route::post('/assessment', [TrainingController::class, 'recordAssessment'])->name('assessment');
        Route::post('/{candidate}/certificate', [TrainingController::class, 'generateCertificate'])->name('certificate');
        Route::get('/batch/{batch}/report', [TrainingController::class, 'batchReport'])->name('batch-report');

        // NEW ROUTES (From updated controller)
        Route::get('/attendance/form', [TrainingController::class, 'attendance'])->name('attendance-form');
        Route::post('/{candidate}/mark-attendance', [TrainingController::class, 'markAttendance'])->name('mark-attendance');

        // THROTTLE FIX: Bulk attendance limited to 30/min (database intensive)
        Route::post('/attendance/bulk', [TrainingController::class, 'bulkAttendance'])
            ->middleware('throttle:30,1')->name('bulk-attendance');

        Route::get('/{candidate}/assessment', [TrainingController::class, 'assessment'])->name('assessment-view');
        Route::post('/{candidate}/store-assessment', [TrainingController::class, 'storeAssessment'])->name('store-assessment');
        Route::put('/assessment/{assessment}', [TrainingController::class, 'updateAssessment'])->name('update-assessment');
        Route::get('/{candidate}/certificate/download', [TrainingController::class, 'downloadCertificate'])->name('download-certificate');
        Route::post('/{candidate}/complete', [TrainingController::class, 'complete'])->name('complete');

        // THROTTLE FIX: Reports limited to 5/min (resource intensive)
        Route::post('/reports/attendance', [TrainingController::class, 'attendanceReport'])
            ->middleware('throttle:5,1')->name('attendance-report');
        Route::post('/reports/assessment', [TrainingController::class, 'assessmentReport'])
            ->middleware('throttle:5,1')->name('assessment-report');

        Route::get('/batch/{batch}/performance', [TrainingController::class, 'batchPerformance'])->name('batch-performance');
    });

    // ========================================================================
    // VISA PROCESSING ROUTES
    // Throttle: Standard 60/min, Upload 30/min, Reports 5/min
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

        // THROTTLE FIX: Ticket upload limited to 30/min (file upload)
        Route::post('/{candidate}/ticket', [VisaProcessingController::class, 'uploadTicket'])
            ->middleware('throttle:30,1')->name('ticket');

        // THROTTLE FIX: Timeline report limited to 5/min (resource intensive)
        Route::get('/timeline-report', [VisaProcessingController::class, 'timelineReport'])
            ->middleware('throttle:5,1')->name('timeline-report');

        // NEW ROUTES (From updated controller)
        Route::post('/{candidate}/update-interview', [VisaProcessingController::class, 'updateInterview'])->name('update-interview'); // NEW
        Route::post('/{candidate}/update-trade-test', [VisaProcessingController::class, 'updateTradeTest'])->name('update-trade-test'); // NEW
        Route::post('/{candidate}/update-takamol', [VisaProcessingController::class, 'updateTakamol'])->name('update-takamol'); // NEW
        Route::post('/{candidate}/update-medical', [VisaProcessingController::class, 'updateMedical'])->name('update-medical'); // NEW
        Route::post('/{candidate}/update-biometric', [VisaProcessingController::class, 'updateBiometric'])->name('update-biometric'); // NEW
        Route::post('/{candidate}/update-visa', [VisaProcessingController::class, 'updateVisa'])->name('update-visa'); // NEW
        Route::get('/{candidate}/timeline', [VisaProcessingController::class, 'timeline'])->name('timeline'); // NEW

        // THROTTLE FIX: Overdue report limited to 5/min (resource intensive)
        Route::get('/reports/overdue', [VisaProcessingController::class, 'overdue'])
            ->middleware('throttle:5,1')->name('overdue');

        Route::post('/{candidate}/complete', [VisaProcessingController::class, 'complete'])->name('complete'); // NEW

        // THROTTLE FIX: Report generation limited to 5/min (resource intensive)
        Route::post('/reports/generate', [VisaProcessingController::class, 'report'])
            ->middleware('throttle:5,1')->name('report');
    });

    // ========================================================================
    // DEPARTURE ROUTES
    // Throttle: Standard 60/min, Reports 5/min
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

        // THROTTLE FIX: Compliance report limited to 5/min (resource intensive)
        Route::post('/reports/compliance', [DepartureController::class, 'complianceReport'])
            ->middleware('throttle:5,1')->name('compliance-report');

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
    // COMPLAINTS MANAGEMENT
    // Throttle: Standard 60/min, Escalate 30/min, Reports/Export 5/min
    // ========================================================================
    Route::resource('complaints', ComplaintController::class);
    Route::prefix('complaints')->name('complaints.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/overdue', [ComplaintController::class, 'overdue'])->name('overdue');
        Route::post('/{complaint}/assign', [ComplaintController::class, 'assign'])->name('assign');
        Route::post('/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('resolve');

        // THROTTLE FIX: Escalate limited to 30/min (important workflow action)
        Route::post('/{complaint}/escalate', [ComplaintController::class, 'escalate'])
            ->middleware('throttle:30,1')->name('escalate');

        Route::get('/statistics', [ComplaintController::class, 'statistics'])->name('statistics');

        // NEW ROUTES (From updated controller)
        Route::post('/{complaint}/update', [ComplaintController::class, 'addUpdate'])->name('add-update');
        Route::post('/{complaint}/evidence', [ComplaintController::class, 'addEvidence'])->name('add-evidence');
        Route::post('/{complaint}/close', [ComplaintController::class, 'close'])->name('close');
        Route::post('/{complaint}/reopen', [ComplaintController::class, 'reopen'])->name('reopen');
        Route::get('/category/{category}', [ComplaintController::class, 'byCategory'])->name('by-category');
        Route::get('/my/assignments', [ComplaintController::class, 'myAssignments'])->name('my-assignments');

        // THROTTLE FIX: Reports and exports limited to 5/min (resource intensive)
        Route::post('/reports/analytics', [ComplaintController::class, 'analytics'])
            ->middleware('throttle:5,1')->name('analytics');
        Route::post('/reports/sla', [ComplaintController::class, 'slaReport'])
            ->middleware('throttle:5,1')->name('sla-report');
        Route::post('/export', [ComplaintController::class, 'export'])
            ->middleware('throttle:5,1')->name('export');
    });

    // ========================================================================
    // DOCUMENT ARCHIVE
    // Throttle: Standard 60/min, Download 60/min, Bulk upload 10/min, Reports 5/min
    // ========================================================================
    Route::resource('document-archive', DocumentArchiveController::class)->except(['create', 'edit']);
    Route::prefix('document-archive')->name('document-archive.')->group(function () {
        // EXISTING ROUTES (Keep for backward compatibility)
        Route::get('/expiring', [DocumentArchiveController::class, 'expiring'])->name('expiring');
        Route::get('/search', [DocumentArchiveController::class, 'search'])->name('search');
        Route::get('/{document}/versions', [DocumentArchiveController::class, 'versions'])->name('versions');

        // THROTTLE FIX: Download limited to 60/min (bandwidth management)
        Route::get('/{document}/download', [DocumentArchiveController::class, 'download'])
            ->middleware('throttle:60,1')->name('download');

        // NEW ROUTES (From updated controller)
        Route::get('/create', [DocumentArchiveController::class, 'create'])->name('create');
        Route::get('/{document}/edit', [DocumentArchiveController::class, 'edit'])->name('edit');
        Route::post('/{document}/version', [DocumentArchiveController::class, 'uploadVersion'])->name('upload-version');
        Route::get('/{document}/view', [DocumentArchiveController::class, 'view'])->name('view');
        Route::post('/{document}/restore-version', [DocumentArchiveController::class, 'restoreVersion'])->name('restore-version');
        Route::get('/tracking/expired', [DocumentArchiveController::class, 'expired'])->name('expired');
        Route::get('/candidate/{candidate}/documents', [DocumentArchiveController::class, 'candidateDocuments'])->name('candidate-documents');
        Route::get('/{document}/access-logs', [DocumentArchiveController::class, 'accessLogs'])->name('access-logs');
        Route::get('/reports/statistics', [DocumentArchiveController::class, 'statistics'])->name('statistics');

        // THROTTLE FIX: Report generation limited to 5/min (CPU intensive)
        Route::post('/reports/generate', [DocumentArchiveController::class, 'report'])
            ->middleware('throttle:5,1')->name('report');

        // THROTTLE FIX: Bulk upload limited to 10/min (storage abuse prevention)
        Route::post('/bulk/upload', [DocumentArchiveController::class, 'bulkUpload'])
            ->middleware('throttle:10,1')->name('bulk-upload');

        Route::post('/{document}/archive', [DocumentArchiveController::class, 'archive'])->name('archive');
        Route::post('/{document}/restore', [DocumentArchiveController::class, 'restore'])->name('restore');
        Route::post('/reminders/send', [DocumentArchiveController::class, 'sendExpiryReminders'])->name('send-expiry-reminders');
    });

    // ========================================================================
    // REPORTS
    // Throttle: Standard 60/min, Generate custom 3/min (very CPU intensive)
    // ========================================================================
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

        // THROTTLE FIX: Custom report generation limited to 3/min (very CPU intensive)
        Route::post('/generate-custom', [ReportController::class, 'generateCustomReport'])
            ->middleware('throttle:3,1')->name('generate-custom');

        // THROTTLE FIX: Export limited to 5/min (resource intensive)
        Route::get('/export/{type}', [ReportController::class, 'export'])
            ->middleware('throttle:5,1')->name('export');
    });

    // ========================================================================
    // ADMIN ROUTES
    // Access: Admin only | Middleware: role:admin
    // ========================================================================
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