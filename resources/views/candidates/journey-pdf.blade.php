<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Candidate Journey - {{ $candidate->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin: 0;
        }
        .header p {
            color: #6b7280;
            margin: 5px 0 0;
        }
        .candidate-info {
            background: #f3f4f6;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .candidate-info table {
            width: 100%;
        }
        .candidate-info td {
            padding: 5px 10px;
        }
        .candidate-info .label {
            color: #6b7280;
            font-weight: bold;
        }
        .progress-section {
            margin-bottom: 30px;
        }
        .progress-bar {
            background: #e5e7eb;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            background: #3b82f6;
            height: 100%;
            text-align: center;
            color: white;
            line-height: 20px;
            font-size: 11px;
        }
        .timeline {
            margin-top: 30px;
        }
        .timeline-item {
            display: flex;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .timeline-marker {
            width: 30px;
            text-align: center;
            margin-right: 15px;
        }
        .timeline-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            line-height: 20px;
            font-size: 10px;
            color: white;
        }
        .timeline-dot.completed {
            background: #22c55e;
        }
        .timeline-dot.in_progress {
            background: #3b82f6;
        }
        .timeline-dot.pending {
            background: #d1d5db;
            color: #6b7280;
        }
        .timeline-content {
            flex: 1;
            border-left: 2px solid #e5e7eb;
            padding-left: 15px;
        }
        .timeline-content h3 {
            margin: 0 0 5px;
            font-size: 14px;
        }
        .timeline-content .date {
            color: #6b7280;
            font-size: 11px;
        }
        .timeline-content .details {
            background: #f9fafb;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
        }
        .milestones {
            margin-top: 30px;
            page-break-before: always;
        }
        .milestone-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .milestone-item .check {
            color: #22c55e;
            margin-right: 10px;
        }
        .milestone-item .pending {
            color: #d1d5db;
            margin-right: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WASL - Candidate Journey Report</h1>
        <p>{{ $candidate->name }} | TheLeap ID: {{ $candidate->btevta_id ?? 'Not Assigned' }}</p>
    </div>

    <div class="candidate-info">
        <table>
            <tr>
                <td class="label">Full Name:</td>
                <td>{{ $candidate->name }}</td>
                <td class="label">CNIC:</td>
                <td>{{ $candidate->cnic }}</td>
            </tr>
            <tr>
                <td class="label">Campus:</td>
                <td>{{ $candidate->campus?->name ?? 'N/A' }}</td>
                <td class="label">Trade:</td>
                <td>{{ $candidate->trade?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Batch:</td>
                <td>{{ $candidate->batch?->name ?? 'Not Assigned' }}</td>
                <td class="label">OEP:</td>
                <td>{{ $candidate->oep?->name ?? 'Not Assigned' }}</td>
            </tr>
            <tr>
                <td class="label">Current Status:</td>
                <td><strong>{{ $currentStage['name'] }}</strong></td>
                <td class="label">Report Date:</td>
                <td>{{ now()->format('M d, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    <div class="progress-section">
        <h2>Overall Progress</h2>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $progressPercentage }}%">
                {{ $progressPercentage }}% Complete
            </div>
        </div>
    </div>

    <div class="timeline">
        <h2>Journey Timeline</h2>
        @foreach($journey as $index => $stage)
            <div class="timeline-item">
                <div class="timeline-marker">
                    <span class="timeline-dot {{ $stage['status'] }}">
                        @if($stage['status'] === 'completed')
                            ✓
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                </div>
                <div class="timeline-content">
                    <h3>{{ $stage['name'] }}</h3>
                    <div class="date">
                        Module {{ $stage['module'] }}
                        @if($stage['completed_at'])
                            | Completed: {{ \Carbon\Carbon::parse($stage['completed_at'])->format('M d, Y') }}
                        @elseif($stage['status'] === 'in_progress')
                            | Currently In Progress
                        @else
                            | Pending
                        @endif
                    </div>
                    @if(count($stage['details']) > 0)
                        <div class="details">
                            @foreach($stage['details'] as $key => $value)
                                @if(!is_array($value) && $value)
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}<br>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="milestones">
        <h2>Key Milestones</h2>
        @foreach($milestones as $milestone)
            <div class="milestone-item">
                <span>
                    @if($milestone['completed'])
                        <span class="check">✓</span>
                    @else
                        <span class="pending">○</span>
                    @endif
                    {{ $milestone['name'] }}
                </span>
                <span>
                    @if($milestone['date'])
                        {{ \Carbon\Carbon::parse($milestone['date'])->format('M d, Y') }}
                    @else
                        -
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>This report was generated by the WASL System on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>Board of Technical Education & Vocational Training Authority (BTEVTA) Punjab</p>
    </div>
</body>
</html>
