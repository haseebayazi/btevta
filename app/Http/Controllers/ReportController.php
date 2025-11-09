<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\Oep;
use App\Models\TrainingAssessment;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function candidateProfile(Candidate $candidate)
    {
        $candidate->load([
            'trade',
            'campus',
            'batch',
            'screenings',
            'documents',
            'nextOfKin',
            'undertakings',
            'attendances',
            'assessments',
            'certificate',
            'visaProcess',
            'departure',
            'complaints'
        ]);

        return view('reports.candidate-profile', compact('candidate'));
    }

    public function batchSummary(Batch $batch)
    {
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
        $oeps = Oep::withCount([
            'candidates',
            'candidates as departed_count' => fn($q) => $q->where('status', 'departed'),
        ])->get();

        return view('reports.oep-performance', compact('oeps'));
    }

    public function visaTimeline()
    {
        $visaData = DB::table('visa_processes')
            ->selectRaw('
                COUNT(*) as total,
                AVG(DATEDIFF(visa_issue_date, interview_date)) as avg_days,
                MIN(visa_issue_date) as earliest,
                MAX(visa_issue_date) as latest
            ')
            ->first();

        $byStage = DB::table('visa_processes')
            ->selectRaw('current_stage, COUNT(*) as count')
            ->groupBy('current_stage')
            ->get();

        return view('reports.visa-timeline', compact('visaData', 'byStage'));
    }

    public function trainingStatistics()
    {
        $totalInTraining = Candidate::where('status', 'training')->count();
        $totalCompleted = Candidate::where('status', 'departed')->count();

        $assessmentStats = TrainingAssessment::selectRaw('
            result,
            assessment_type,
            COUNT(*) as count,
            AVG(percentage) as avg_percentage
        ')
        ->groupBy('result', 'assessment_type')
        ->get();

        $attendanceStats = DB::table('training_attendances')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
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
        $complaintsByStatus = Complaint::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $complaintsByPriority = Complaint::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        $overdueComplaints = Complaint::where('status', '!=', 'resolved')
            ->whereRaw('DATE_ADD(registered_at, INTERVAL CAST(sla_days AS SIGNED) DAY) < NOW()')
            ->count();

        $averageResolutionTime = DB::table('complaints')
            ->where('status', 'resolved')
            ->selectRaw('AVG(DATEDIFF(resolved_at, registered_at)) as avg_days')
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
        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'status' => 'nullable|in:listed,screening,registered,training,visa_processing,departed,rejected',
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

        $data = $query->get();

        if ($validated['format'] === 'excel') {
            return $this->exportToExcel($data, 'custom_report');
        }

        return view('reports.custom-report-result', compact('data'));
    }

    public function export(Request $request, $type)
    {
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

        $data = $query->get();

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
}