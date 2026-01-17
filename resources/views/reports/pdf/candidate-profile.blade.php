<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Profile - {{ $candidate->btevta_id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #1e40af;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            background-color: #1e40af;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 3px 5px;
            font-weight: bold;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
        }
        .info-value {
            display: table-cell;
            padding: 3px 5px;
            border: 1px solid #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-active { background-color: #dcfce7; color: #166534; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-completed { background-color: #dbeafe; color: #1e40af; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TheLeap Candidate Profile</h1>
        <p>Generated on {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <!-- Personal Information -->
    <div class="section">
        <div class="section-title">Personal Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">TheLeap ID</div>
                <div class="info-value">{{ $candidate->btevta_id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CNIC</div>
                <div class="info-value">{{ $candidate->cnic }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Name</div>
                <div class="info-value">{{ $candidate->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Father Name</div>
                <div class="info-value">{{ $candidate->father_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender</div>
                <div class="info-value">{{ ucfirst($candidate->gender ?? 'N/A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone</div>
                <div class="info-value">{{ $candidate->phone ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="status-badge status-active">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Information -->
    <div class="section">
        <div class="section-title">Assignment Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Trade</div>
                <div class="info-value">{{ $candidate->trade?->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Campus</div>
                <div class="info-value">{{ $candidate->campus?->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Batch</div>
                <div class="info-value">{{ $candidate->batch?->batch_code ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">OEP</div>
                <div class="info-value">{{ $candidate->oep?->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Documents -->
    @if($candidate->documents->count() > 0)
    <div class="section">
        <div class="section-title">Documents ({{ $candidate->documents->count() }})</div>
        <table>
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Upload Date</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($candidate->documents as $doc)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                    <td>{{ $doc->expiry_date ? \Carbon\Carbon::parse($doc->expiry_date)->format('d M Y') : 'N/A' }}</td>
                    <td>{{ ucfirst($doc->verification_status ?? 'Pending') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Training Summary -->
    @if($candidate->trainingAttendances->count() > 0 || $candidate->trainingAssessments->count() > 0)
    <div class="section">
        <div class="section-title">Training Summary</div>
        <div class="info-grid">
            @php
                $totalAttendance = $candidate->trainingAttendances->count();
                $presentDays = $candidate->trainingAttendances->where('status', 'present')->count();
                $attendanceRate = $totalAttendance > 0 ? round(($presentDays / $totalAttendance) * 100, 1) : 0;
                $avgScore = $candidate->trainingAssessments->avg('score') ?? 0;
            @endphp
            <div class="info-row">
                <div class="info-label">Attendance Rate</div>
                <div class="info-value">{{ $attendanceRate }}% ({{ $presentDays }}/{{ $totalAttendance }} days)</div>
            </div>
            <div class="info-row">
                <div class="info-label">Average Assessment Score</div>
                <div class="info-value">{{ round($avgScore, 1) }}%</div>
            </div>
            <div class="info-row">
                <div class="info-label">Certificates Issued</div>
                <div class="info-value">{{ $candidate->trainingCertificates->count() }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Visa Process -->
    @if($candidate->visaProcess)
    <div class="section">
        <div class="section-title">Visa Process Status</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Interview</div>
                <div class="info-value">{{ $candidate->visaProcess->interview_completed ? 'Completed' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Trade Test</div>
                <div class="info-value">{{ $candidate->visaProcess->trade_test_completed ? 'Completed' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Medical</div>
                <div class="info-value">{{ $candidate->visaProcess->medical_completed ? 'Completed' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Biometric</div>
                <div class="info-value">{{ $candidate->visaProcess->biometric_completed ? 'Completed' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Visa Issued</div>
                <div class="info-value">{{ $candidate->visaProcess->visa_issued ? 'Yes - ' . ($candidate->visaProcess->visa_number ?? 'N/A') : 'Pending' }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Departure Information -->
    @if($candidate->departure)
    <div class="section">
        <div class="section-title">Departure Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Departure Date</div>
                <div class="info-value">{{ $candidate->departure->departure_date ? \Carbon\Carbon::parse($candidate->departure->departure_date)->format('d M Y') : 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Iqama Number</div>
                <div class="info-value">{{ $candidate->departure->iqama_number ?? 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Absher Registration</div>
                <div class="info-value">{{ $candidate->departure->absher_registered ? 'Registered' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Salary Status</div>
                <div class="info-value">{{ $candidate->departure->salary_confirmed ? 'Confirmed' : 'Pending' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">90-Day Compliance</div>
                <div class="info-value">{{ $candidate->departure->ninety_day_report_submitted ? 'Verified' : 'Pending' }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Next of Kin -->
    @if($candidate->nextOfKin)
    <div class="section">
        <div class="section-title">Next of Kin</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Name</div>
                <div class="info-value">{{ $candidate->nextOfKin->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Relationship</div>
                <div class="info-value">{{ $candidate->nextOfKin->relationship ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone</div>
                <div class="info-value">{{ $candidate->nextOfKin->phone ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This is an auto-generated document from TheLeap Candidate Management System</p>
        <p>Document ID: {{ $candidate->btevta_id }}-{{ now()->format('YmdHis') }}</p>
    </div>
</body>
</html>
