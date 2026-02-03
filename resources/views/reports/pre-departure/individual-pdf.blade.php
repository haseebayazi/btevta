<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Departure Document Report - {{ $candidate->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
            margin-bottom: 15px;
        }
        .report-title {
            font-size: 16pt;
            font-weight: bold;
            color: #1e40af;
            margin-top: 10px;
        }
        .candidate-info {
            background-color: #f3f4f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .progress-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9fafb;
            border-left: 4px solid #1e40af;
        }
        .progress-bar-container {
            background-color: #e5e7eb;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar {
            height: 100%;
            background-color: #10b981;
            text-align: center;
            color: white;
            font-size: 9pt;
            line-height: 20px;
        }
        .progress-bar.incomplete {
            background-color: #f59e0b;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            margin: 25px 0 15px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #1e40af;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10pt;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        .status-verified {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-missing {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 9pt;
            color: #6b7280;
        }
        .footer-grid {
            display: table;
            width: 100%;
        }
        .footer-row {
            display: table-row;
        }
        .footer-cell {
            display: table-cell;
            padding: 3px 0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    {{-- Header Section --}}
    <div class="header">
        <div class="logo">BTEVTA - WASL</div>
        <div class="subtitle">Board of Technical Education & Vocational Training Authority</div>
        <div class="subtitle">Workforce Abroad Skills & Linkages (WASL) Program</div>
        <div class="report-title">Pre-Departure Document Report</div>
    </div>

    {{-- Candidate Information --}}
    <div class="candidate-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Candidate Name:</div>
                <div class="info-value">{{ $candidate->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">BTEVTA ID:</div>
                <div class="info-value">{{ $candidate->btevta_id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">CNIC:</div>
                <div class="info-value">{{ $candidate->cnic }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Campus:</div>
                <div class="info-value">{{ $candidate->campus?->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Trade:</div>
                <div class="info-value">{{ $candidate->trade?->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Current Status:</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $candidate->status)) }}</div>
            </div>
        </div>
    </div>

    {{-- Document Completion Progress --}}
    <div class="progress-section">
        <h3 style="margin-bottom: 10px;">Document Completion Status</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Mandatory Documents:</div>
                <div class="info-value">{{ $status['mandatory_uploaded'] }} / {{ $status['mandatory_total'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Optional Documents:</div>
                <div class="info-value">{{ $status['optional_uploaded'] }} / {{ $status['optional_total'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Overall Completion:</div>
                <div class="info-value">{{ $status['completion_percentage'] }}%</div>
            </div>
        </div>
        <div class="progress-bar-container">
            <div class="progress-bar {{ $status['is_complete'] ? '' : 'incomplete' }}"
                 style="width: {{ $status['completion_percentage'] }}%">
                {{ $status['completion_percentage'] }}%
            </div>
        </div>
    </div>

    {{-- Mandatory Documents Table --}}
    <div class="section-title">Mandatory Documents</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 35%">Document Name</th>
                <th style="width: 20%">Status</th>
                <th style="width: 20%">Upload Date</th>
                <th style="width: 20%">Verified By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mandatory as $index => $checklist)
                @php
                    $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $checklist->name }}</td>
                    <td>
                        @if($document)
                            @if($document->isVerified())
                                <span class="status-badge status-verified">Verified</span>
                            @else
                                <span class="status-badge status-pending">Pending Review</span>
                            @endif
                        @else
                            <span class="status-badge status-missing">Missing</span>
                        @endif
                    </td>
                    <td>{{ $document?->uploaded_at?->format('d M, Y') ?? '-' }}</td>
                    <td>{{ $document?->verifier?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Optional Documents Table --}}
    <div class="section-title">Optional Documents</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 35%">Document Name</th>
                <th style="width: 20%">Status</th>
                <th style="width: 20%">Upload Date</th>
                <th style="width: 20%">Verified By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($optional as $index => $checklist)
                @php
                    $document = $documents->firstWhere('document_checklist_id', $checklist->id);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $checklist->name }}</td>
                    <td>
                        @if($document)
                            @if($document->isVerified())
                                <span class="status-badge status-verified">Verified</span>
                            @else
                                <span class="status-badge status-pending">Pending Review</span>
                            @endif
                        @else
                            <span class="status-badge status-missing">Not Uploaded</span>
                        @endif
                    </td>
                    <td>{{ $document?->uploaded_at?->format('d M, Y') ?? '-' }}</td>
                    <td>{{ $document?->verifier?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #6b7280;">No optional documents configured</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer Section --}}
    <div class="footer">
        <div class="footer-grid">
            <div class="footer-row">
                <div class="footer-cell" style="width: 40%">
                    <strong>Generated:</strong> {{ $generated_at->format('d M, Y h:i A') }}
                </div>
                <div class="footer-cell" style="width: 60%">
                    <strong>Generated By:</strong> {{ $generated_by->name }} ({{ $generated_by->email }})
                </div>
            </div>
            <div class="footer-row">
                <div class="footer-cell" colspan="2" style="padding-top: 10px;">
                    <em>This is a system-generated document from BTEVTA WASL Application.</em>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
