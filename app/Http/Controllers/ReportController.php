<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\Oep;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingSchedule;
use App\Models\Complaint;
use App\Models\Departure;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReportController extends Controller
{
    /**
     * Check if user can view reports based on role
     */
    private function canViewReports(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'campus_admin', 'viewer']);
    }

    /**
     * Check if user can view campus-wise reports
     */
    private function canViewCampusReports(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'viewer']);
    }

    /**
     * Check if user can export reports
     */
    private function canExportReports(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'campus_admin']);
    }

    public function index()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        return view('reports.index');
    }

    public function candidateProfile(Candidate $candidate)
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        $candidate->load([
            'trade',
            'campus',
            'batch',
            'screenings',
            'documents',
            'nextOfKin',
            'undertakings',
            'trainingAttendances',
            'trainingAssessments',
            'trainingCertificates',
            'visaProcess',
            'departure',
            'complaints'
        ]);

        return view('reports.candidate-profile', compact('candidate'));
    }

    public function batchSummary(Batch $batch)
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        $batch->load([
            'candidates',
            'candidates.assessments',
            'candidates.attendances'
        ]);

        $totalCandidates = $batch->candidates()->count();
        $passed = $batch->candidates()
            ->whereHas('assessments', function ($q) {
                $q->where('result', 'pass');
            })
            ->count();

        $attendance = DB::table('training_attendances')
            ->where('batch_id', $batch->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return view('reports.batch-summary', compact('batch', 'totalCandidates', 'passed', 'attendance'));
    }

    public function campusPerformance()
    {
        if (!$this->canViewCampusReports()) {
            abort(403, 'You do not have permission to view campus performance reports.');
        }

        $campuses = Campus::withCount([
            'candidates',
            'candidates as registered_count' => fn($q) => $q->where('status', 'registered'),
            'candidates as training_count' => fn($q) => $q->where('status', 'training'),
            'candidates as departed_count' => fn($q) => $q->where('status', 'departed'),
        ])->get();

        return view('reports.campus-performance', compact('campuses'));
    }

    public function oepPerformance()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        // AUDIT FIX: Filter candidate counts by campus for campus admins
        $user = auth()->user();
        $query = Oep::query();

        if ($user->role === 'campus_admin' && $user->campus_id) {
            // Campus admins see all OEPs but with counts filtered to their campus
            $query->withCount([
                'candidates' => fn($q) => $q->where('campus_id', $user->campus_id),
                'candidates as departed_count' => fn($q) => $q->where('status', 'departed')
                    ->where('campus_id', $user->campus_id),
            ]);
        } else {
            // Admins and viewers see all data
            $query->withCount([
                'candidates',
                'candidates as departed_count' => fn($q) => $q->where('status', 'departed'),
            ]);
        }

        $oeps = $query->get();

        return view('reports.oep-performance', compact('oeps'));
    }

    public function visaTimeline()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        // AUDIT FIX: Add campus-based filtering for visa timeline data
        $user = auth()->user();
        $baseQuery = DB::table('visa_processes')
            ->join('candidates', 'visa_processes.candidate_id', '=', 'candidates.id');

        // Apply campus filtering for campus_admin and OEP users
        if ($user->role === 'campus_admin' && $user->campus_id) {
            $baseQuery->where('candidates.campus_id', $user->campus_id);
        } elseif ($user->role === 'oep' && $user->oep_id) {
            $baseQuery->where('candidates.oep_id', $user->oep_id);
        }

        $visaData = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total,
                AVG(DATEDIFF(visa_processes.visa_issue_date, visa_processes.interview_date)) as avg_days,
                MIN(visa_processes.visa_issue_date) as earliest,
                MAX(visa_processes.visa_issue_date) as latest
            ')
            ->first();

        $byStage = (clone $baseQuery)
            ->selectRaw('visa_processes.current_stage, COUNT(*) as count')
            ->groupBy('visa_processes.current_stage')
            ->get();

        return view('reports.visa-timeline', compact('visaData', 'byStage'));
    }

    public function trainingStatistics()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        // AUDIT FIX: Add campus-based filtering for training statistics
        $user = auth()->user();
        $candidateQuery = Candidate::query();
        $assessmentQuery = TrainingAssessment::query();
        $attendanceQuery = DB::table('training_attendances')
            ->join('candidates', 'training_attendances.candidate_id', '=', 'candidates.id');

        // Apply campus filtering for campus_admin and OEP users
        if ($user->role === 'campus_admin' && $user->campus_id) {
            $candidateQuery->where('campus_id', $user->campus_id);
            $assessmentQuery->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            $attendanceQuery->where('candidates.campus_id', $user->campus_id);
        } elseif ($user->role === 'oep' && $user->oep_id) {
            $candidateQuery->where('oep_id', $user->oep_id);
            $assessmentQuery->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            $attendanceQuery->where('candidates.oep_id', $user->oep_id);
        }

        $totalInTraining = (clone $candidateQuery)->where('status', 'training')->count();
        $totalCompleted = (clone $candidateQuery)->where('status', 'departed')->count();

        $assessmentStats = $assessmentQuery->selectRaw('
            result,
            assessment_type,
            COUNT(*) as count,
            AVG(percentage) as avg_percentage
        ')
        ->groupBy('result', 'assessment_type')
        ->get();

        $attendanceStats = $attendanceQuery
            ->selectRaw('training_attendances.status, COUNT(*) as count')
            ->groupBy('training_attendances.status')
            ->get();

        return view('reports.training-statistics', compact(
            'totalInTraining',
            'totalCompleted',
            'assessmentStats',
            'attendanceStats'
        ));
    }

    public function complaintAnalysis()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        // AUDIT FIX: Add campus-based filtering for complaint analysis
        $user = auth()->user();

        $baseQuery = Complaint::query();
        $baseDbQuery = DB::table('complaints')
            ->join('candidates', 'complaints.candidate_id', '=', 'candidates.id');

        // Apply campus filtering for campus_admin and OEP users
        if ($user->role === 'campus_admin' && $user->campus_id) {
            $baseQuery->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            $baseDbQuery->where('candidates.campus_id', $user->campus_id);
        } elseif ($user->role === 'oep' && $user->oep_id) {
            $baseQuery->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            $baseDbQuery->where('candidates.oep_id', $user->oep_id);
        }

        $complaintsByStatus = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $complaintsByPriority = (clone $baseQuery)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        $overdueComplaints = (clone $baseQuery)
            ->where('status', '!=', 'resolved')
            ->whereRaw('DATE_ADD(registered_at, INTERVAL CAST(sla_days AS SIGNED) DAY) < NOW()')
            ->count();

        $averageResolutionTime = (clone $baseDbQuery)
            ->where('complaints.status', 'resolved')
            ->selectRaw('AVG(DATEDIFF(complaints.resolved_at, complaints.registered_at)) as avg_days')
            ->first();

        return view('reports.complaint-analysis', compact(
            'complaintsByStatus',
            'complaintsByPriority',
            'overdueComplaints',
            'averageResolutionTime'
        ));
    }

    public function customReport()
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        $campuses = Campus::where('is_active', true)->get();
        $statuses = [
            'listed' => 'Listed',
            'screening' => 'Screening',
            'registered' => 'Registered',
            'training' => 'Training',
            'visa_processing' => 'Visa Processing',
            'departed' => 'Departed',
            'rejected' => 'Rejected'
        ];

        return view('reports.custom-report', compact('campuses', 'statuses'));
    }

    public function generateCustomReport(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403, 'You do not have permission to view reports.');
        }

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'status' => 'nullable|in:new,screening,registered,training,visa_process,ready,departed,rejected,dropped',
            'trade_id' => 'nullable|exists:trades,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'format' => 'required|in:view,excel'
        ]);

        $query = Candidate::with(['trade', 'campus', 'batch']);

        if ($validated['campus_id'] ?? null) {
            $query->where('campus_id', $validated['campus_id']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['trade_id'] ?? null) {
            $query->where('trade_id', $validated['trade_id']);
        }

        if ($validated['date_from'] ?? null) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // AUDIT FIX: Limit results and use pagination for custom reports
        $data = $query->limit(5000)->get();

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'custom_report');
        }

        // Paginate for view display
        $data = $query->paginate(50);

        return view('reports.custom-report-result', compact('data'));
    }

    public function export(Request $request, $type)
    {
        if (!$this->canExportReports()) {
            abort(403, 'You do not have permission to export reports.');
        }

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'status' => 'nullable|string',
        ]);

        $query = Candidate::with(['trade', 'campus', 'batch', 'oep']);

        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        if ($validated['campus_id'] ?? null) {
            $query->where('campus_id', $validated['campus_id']);
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        // AUDIT FIX: Limit export results to prevent memory exhaustion
        $data = $query->limit(10000)->get();

        if ($type === 'excel') {
            return $this->exportToExcel($data, 'report');
        }

        return redirect()->back()->with('error', 'Invalid export format');
    }

    private function exportToExcel($data, $filename)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'BTEVTA ID', 'CNIC', 'Name', 'Father Name',
                'Gender', 'Phone', 'Trade', 'Campus', 'Status', 'Created'
            ];
            $sheet->fromArray($headers, null, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $rowData = [
                    $item->btevta_id,
                    $item->cnic,
                    $item->name,
                    $item->father_name,
                    $item->gender,
                    $item->phone,
                    $item->trade?->name ?? 'N/A',
                    $item->campus?->name ?? 'N/A',
                    $item->status_label,
                    $item->created_at->format('Y-m-d'),
                ];
                $sheet->fromArray($rowData, null, 'A' . $row);
                $row++;
            }

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            ];
            $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $tempFile = storage_path('app/temp/' . $filename . '_' . date('YmdHis') . '.xlsx');

            if (!is_dir(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            $writer->save($tempFile);

            return response()->download($tempFile, basename($tempFile))->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export candidate profile as PDF
     */
    public function exportProfilePdf(Candidate $candidate)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        try {
            $candidate->load([
                'trade', 'campus', 'batch', 'oep', 'screenings',
                'documents', 'nextOfKin', 'undertakings',
                'trainingAttendances', 'trainingAssessments',
                'trainingCertificates', 'visaProcess', 'departure'
            ]);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf.candidate-profile', compact('candidate'));

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download("candidate-profile-{$candidate->btevta_id}.pdf");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PDF export failed: ' . $e->getMessage());
        }
    }

    /**
     * Export data as CSV
     */
    public function exportToCsv(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:candidates,departures,complaints,training',
        ]);

        try {
            $type = $validated['type'];
            $filename = $type . '_export_' . date('YmdHis') . '.csv';
            $tempFile = storage_path('app/temp/' . $filename);

            if (!is_dir(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            $file = fopen($tempFile, 'w');

            // AUDIT FIX: Use chunking to prevent memory exhaustion on large datasets
            switch ($type) {
                case 'candidates':
                    fputcsv($file, ['BTEVTA ID', 'CNIC', 'Name', 'Father Name', 'Gender', 'Phone', 'Trade', 'Campus', 'Status', 'Created']);
                    Candidate::with(['trade', 'campus'])->chunk(500, function($candidates) use ($file) {
                        foreach ($candidates as $item) {
                            fputcsv($file, [
                                $item->btevta_id,
                                $item->cnic,
                                $item->name,
                                $item->father_name,
                                $item->gender,
                                $item->phone,
                                $item->trade?->name ?? 'N/A',
                                $item->campus?->name ?? 'N/A',
                                $item->status,
                                $item->created_at->format('Y-m-d'),
                            ]);
                        }
                    });
                    break;

                case 'departures':
                    fputcsv($file, ['BTEVTA ID', 'Name', 'Trade', 'OEP', 'Departure Date', 'Iqama', 'Absher', 'Salary Status']);
                    Departure::with(['candidate.trade', 'candidate.oep'])->chunk(500, function($departures) use ($file) {
                        foreach ($departures as $item) {
                            fputcsv($file, [
                                $item->candidate?->btevta_id ?? 'N/A',
                                $item->candidate?->name ?? 'N/A',
                                $item->candidate?->trade?->name ?? 'N/A',
                                $item->candidate?->oep?->name ?? 'N/A',
                                $item->departure_date ?? 'N/A',
                                $item->iqama_number ?? 'Pending',
                                $item->absher_registered ? 'Registered' : 'Pending',
                                $item->salary_confirmed ? 'Confirmed' : ($item->first_salary_date ? 'Received' : 'Pending'),
                            ]);
                        }
                    });
                    break;

                case 'complaints':
                    fputcsv($file, ['ID', 'Candidate', 'Category', 'Priority', 'Status', 'Created', 'Resolved']);
                    Complaint::with(['candidate'])->chunk(500, function($complaints) use ($file) {
                        foreach ($complaints as $item) {
                            fputcsv($file, [
                                $item->id,
                                $item->candidate?->name ?? 'N/A',
                                $item->category,
                                $item->priority,
                                $item->status,
                                $item->created_at->format('Y-m-d'),
                                $item->resolved_at?->format('Y-m-d') ?? 'N/A',
                            ]);
                        }
                    });
                    break;

                case 'training':
                    fputcsv($file, ['BTEVTA ID', 'Name', 'Batch', 'Campus', 'Attendance %', 'Assessment Score', 'Certificate']);
                    Candidate::with(['batch', 'campus', 'trainingAttendances', 'trainingAssessments', 'trainingCertificates'])
                        ->whereIn('status', ['training', 'visa_process', 'departed'])
                        ->chunk(500, function($candidates) use ($file) {
                            foreach ($candidates as $item) {
                                $totalAttendance = $item->trainingAttendances->count();
                                $presentAttendance = $item->trainingAttendances->where('status', 'present')->count();
                                $attendanceRate = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 0;
                                $avgScore = $item->trainingAssessments->avg('score') ?? 0;

                                fputcsv($file, [
                                    $item->btevta_id,
                                    $item->name,
                                    $item->batch?->batch_code ?? 'N/A',
                                    $item->campus?->name ?? 'N/A',
                                    $attendanceRate . '%',
                                    round($avgScore, 1),
                                    $item->trainingCertificates->count() > 0 ? 'Yes' : 'No',
                                ]);
                            }
                        });
                    break;
            }

            fclose($file);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'CSV export failed: ' . $e->getMessage());
        }
    }

    /**
     * Trainer/Instructor performance report
     */
    public function trainerPerformance(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        try {
            $query = Instructor::with(['campus']);

            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            $instructors = $query->get()->map(function($instructor) {
                // Get batch IDs where this instructor taught
                $batchIds = TrainingSchedule::where('instructor_id', $instructor->id)
                    ->pluck('batch_id')->unique();

                // Calculate metrics
                $totalStudents = Candidate::whereIn('batch_id', $batchIds)->count();
                $totalAssessments = TrainingAssessment::whereIn('batch_id', $batchIds)->count();
                $passedAssessments = TrainingAssessment::whereIn('batch_id', $batchIds)
                    ->where('result', 'pass')->count();
                $totalAttendance = TrainingAttendance::whereIn('batch_id', $batchIds)->count();
                $presentAttendance = TrainingAttendance::whereIn('batch_id', $batchIds)
                    ->where('status', 'present')->count();

                $instructor->total_batches = $batchIds->count();
                $instructor->total_students = $totalStudents;
                $instructor->pass_rate = $totalAssessments > 0
                    ? round(($passedAssessments / $totalAssessments) * 100, 1)
                    : 0;
                $instructor->attendance_rate = $totalAttendance > 0
                    ? round(($presentAttendance / $totalAttendance) * 100, 1)
                    : 0;
                $instructor->avg_score = TrainingAssessment::whereIn('batch_id', $batchIds)->avg('score') ?? 0;

                return $instructor;
            });

            // Summary stats
            $stats = [
                'total_instructors' => $instructors->count(),
                'avg_pass_rate' => $instructors->avg('pass_rate') ?? 0,
                'avg_attendance_rate' => $instructors->avg('attendance_rate') ?? 0,
                'total_students_taught' => $instructors->sum('total_students'),
            ];

            $campuses = Campus::where('is_active', true)->pluck('name', 'id');

            return view('reports.trainer-performance', compact('instructors', 'stats', 'campuses', 'validated'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Salary & post-departure updates report
     */
    public function departureUpdatesReport(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $validated = $request->validate([
            'oep_id' => 'nullable|exists:oeps,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        try {
            $query = Departure::with(['candidate.oep', 'candidate.trade', 'candidate.campus'])
                ->whereNotNull('departure_date');

            if (!empty($validated['oep_id'])) {
                $query->whereHas('candidate', function($q) use ($validated) {
                    $q->where('oep_id', $validated['oep_id']);
                });
            }

            if (!empty($validated['from_date'])) {
                $query->whereDate('departure_date', '>=', $validated['from_date']);
            }
            if (!empty($validated['to_date'])) {
                $query->whereDate('departure_date', '<=', $validated['to_date']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->whereHas('candidate', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                });
            }

            $departures = $query->latest('departure_date')->paginate(20);

            // Summary stats
            $statsQuery = Departure::whereNotNull('departure_date');
            if (auth()->user()->role === 'campus_admin') {
                $statsQuery->whereHas('candidate', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                });
            }

            $stats = [
                'total_departed' => (clone $statsQuery)->count(),
                'briefing_completed' => (clone $statsQuery)->where('briefing_completed', true)->count(),
                'iqama_registered' => (clone $statsQuery)->whereNotNull('iqama_number')->count(),
                'absher_registered' => (clone $statsQuery)->where('absher_registered', true)->count(),
                'qiwa_activated' => (clone $statsQuery)->whereNotNull('qiwa_id')->count(),
                'salary_confirmed' => (clone $statsQuery)->where('salary_confirmed', true)->count(),
                'compliance_verified' => (clone $statsQuery)->where('ninety_day_report_submitted', true)->count(),
                'total_salary_amount' => (clone $statsQuery)->where('salary_confirmed', true)->sum('salary_amount'),
            ];

            $oeps = Oep::where('is_active', true)->pluck('name', 'id');

            return view('reports.departure-updates', compact('departures', 'stats', 'oeps', 'validated'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Instructor/Trainer utilization report
     * Tracks workload, capacity, and efficiency metrics
     */
    public function instructorUtilization(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        try {
            $query = Instructor::with(['campus', 'batches' => function($q) {
                $q->where('status', 'active');
            }]);

            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            $instructors = $query->get()->map(function($instructor) {
                // Current active batches
                $activeBatches = Batch::where('instructor_id', $instructor->id)
                    ->where('status', 'active')
                    ->get();

                // Scheduled hours this week
                $scheduledHoursThisWeek = TrainingSchedule::where('instructor_id', $instructor->id)
                    ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum('duration') / 60; // Convert minutes to hours

                // Total students currently teaching
                $currentStudents = Candidate::whereIn('batch_id', $activeBatches->pluck('id'))
                    ->where('status', 'training')
                    ->count();

                // Completed batches (historical)
                $completedBatches = Batch::where('instructor_id', $instructor->id)
                    ->where('status', 'completed')
                    ->count();

                // Calculate utilization metrics
                $maxBatchesCapacity = $instructor->max_batches_capacity ?? 3; // Default 3 batches max
                $maxHoursPerWeek = $instructor->max_hours_per_week ?? 40; // Default 40 hours

                $instructor->active_batches_count = $activeBatches->count();
                $instructor->current_students = $currentStudents;
                $instructor->completed_batches = $completedBatches;
                $instructor->scheduled_hours_week = round($scheduledHoursThisWeek, 1);
                $instructor->max_batches = $maxBatchesCapacity;
                $instructor->max_hours = $maxHoursPerWeek;
                $instructor->batch_utilization = $maxBatchesCapacity > 0
                    ? round(($activeBatches->count() / $maxBatchesCapacity) * 100, 1)
                    : 0;
                $instructor->hours_utilization = $maxHoursPerWeek > 0
                    ? round(($scheduledHoursThisWeek / $maxHoursPerWeek) * 100, 1)
                    : 0;

                // Status based on utilization
                $avgUtilization = ($instructor->batch_utilization + $instructor->hours_utilization) / 2;
                $instructor->utilization_status = match (true) {
                    $avgUtilization >= 90 => 'overloaded',
                    $avgUtilization >= 70 => 'optimal',
                    $avgUtilization >= 40 => 'underutilized',
                    default => 'available',
                };

                return $instructor;
            });

            // Summary stats
            $stats = [
                'total_instructors' => $instructors->count(),
                'total_active_batches' => $instructors->sum('active_batches_count'),
                'total_current_students' => $instructors->sum('current_students'),
                'avg_batch_utilization' => round($instructors->avg('batch_utilization'), 1),
                'avg_hours_utilization' => round($instructors->avg('hours_utilization'), 1),
                'overloaded' => $instructors->where('utilization_status', 'overloaded')->count(),
                'optimal' => $instructors->where('utilization_status', 'optimal')->count(),
                'underutilized' => $instructors->where('utilization_status', 'underutilized')->count(),
                'available' => $instructors->where('utilization_status', 'available')->count(),
            ];

            $campuses = Campus::where('is_active', true)->pluck('name', 'id');

            return view('reports.instructor-utilization', compact('instructors', 'stats', 'campuses', 'validated'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Performance-based funding metrics report
     */
    public function fundingMetrics(Request $request)
    {
        if (!$this->canViewReports()) {
            abort(403);
        }

        $validated = $request->validate([
            'year' => 'nullable|integer|min:2020|max:' . (date('Y') + 1),
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        $year = $validated['year'] ?? date('Y');

        try {
            $query = \App\Models\CampusKpi::with('campus')
                ->where('year', $year);

            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            $kpis = $query->orderBy('month')->get();

            // Group by campus for comparison
            $campusPerformance = $kpis->groupBy('campus_id')->map(function ($monthlyKpis) {
                return [
                    'campus' => $monthlyKpis->first()->campus,
                    'avg_performance_score' => round($monthlyKpis->avg('performance_score'), 1),
                    'total_candidates_departed' => $monthlyKpis->sum('candidates_departed'),
                    'total_funding_allocated' => $monthlyKpis->sum('funding_allocated'),
                    'total_funding_utilized' => $monthlyKpis->sum('funding_utilized'),
                    'avg_training_completion' => round($monthlyKpis->avg('training_completion_rate'), 1),
                    'avg_attendance' => round($monthlyKpis->avg('attendance_rate'), 1),
                    'months_data' => $monthlyKpis,
                ];
            });

            // Summary stats
            $stats = [
                'total_campuses' => $campusPerformance->count(),
                'avg_performance' => round($campusPerformance->avg('avg_performance_score'), 1),
                'total_departed' => $campusPerformance->sum('total_candidates_departed'),
                'total_allocated' => $campusPerformance->sum('total_funding_allocated'),
                'total_utilized' => $campusPerformance->sum('total_funding_utilized'),
                'utilization_rate' => $campusPerformance->sum('total_funding_allocated') > 0
                    ? round(($campusPerformance->sum('total_funding_utilized') / $campusPerformance->sum('total_funding_allocated')) * 100, 1)
                    : 0,
                'top_performer' => $campusPerformance->sortByDesc('avg_performance_score')->first(),
            ];

            $campuses = Campus::where('is_active', true)->pluck('name', 'id');
            $years = range(date('Y'), 2020);

            return view('reports.funding-metrics', compact('campusPerformance', 'stats', 'campuses', 'years', 'year', 'validated'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Calculate and store KPIs for a campus/month
     */
    public function calculateKpis(Request $request)
    {
        if (!in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $campusId = $validated['campus_id'];
            $year = $validated['year'];
            $month = $validated['month'];

            $kpi = \App\Models\CampusKpi::getOrCreateForMonth($campusId, $year, $month);

            // Calculate candidate metrics
            $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $kpi->candidates_registered = Candidate::where('campus_id', $campusId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $kpi->candidates_trained = Candidate::where('campus_id', $campusId)
                ->where('status', 'training')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            $kpi->candidates_departed = Departure::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                ->whereBetween('departure_date', [$startDate, $endDate])
                ->count();

            $kpi->candidates_rejected = Candidate::where('campus_id', $campusId)
                ->where('status', 'rejected')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count();

            // Calculate training metrics
            $totalAttendance = TrainingAttendance::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                ->whereBetween('date', [$startDate, $endDate])
                ->count();

            $presentAttendance = TrainingAttendance::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'present')
                ->count();

            $kpi->attendance_rate = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 2) : 0;

            $totalAssessments = TrainingAssessment::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $passedAssessments = TrainingAssessment::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('result', 'pass')
                ->count();

            $kpi->assessment_pass_rate = $totalAssessments > 0 ? round(($passedAssessments / $totalAssessments) * 100, 2) : 0;

            // Calculate compliance metrics
            $totalResolved = Complaint::where('campus_id', $campusId)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->count();

            $withinSla = Complaint::where('campus_id', $campusId)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereColumn('resolved_at', '<=', DB::raw('DATE_ADD(created_at, INTERVAL sla_days DAY)'))
                ->count();

            $kpi->complaint_resolution_rate = $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 2) : 100;

            // Calculate performance score
            $kpi->performance_score = $kpi->calculatePerformanceScore();
            $kpi->performance_grade = \App\Models\CampusKpi::getGrade($kpi->performance_score);

            $kpi->calculated_by = auth()->id();
            $kpi->calculated_at = now();
            $kpi->save();

            return redirect()->back()->with('success', 'KPIs calculated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to calculate KPIs: ' . $e->getMessage());
        }
    }
}