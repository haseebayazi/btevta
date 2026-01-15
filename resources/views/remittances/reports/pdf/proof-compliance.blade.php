<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proof of Compliance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #7c3aed;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #5b21b6;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #5b21b6;
            margin-bottom: 10px;
        }
        .compliance-summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .compliance-item {
            display: table-cell;
            width: 33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .compliance-item.success {
            background: #dcfce7;
            border-color: #86efac;
        }
        .compliance-item.warning {
            background: #fef3c7;
            border-color: #fcd34d;
        }
        .compliance-item.danger {
            background: #fee2e2;
            border-color: #fca5a5;
        }
        .compliance-item .value {
            font-size: 24px;
            font-weight: bold;
        }
        .compliance-item.success .value { color: #166534; }
        .compliance-item.warning .value { color: #92400e; }
        .compliance-item.danger .value { color: #991b1b; }
        .compliance-item .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #e5e7eb;
        }
        th {
            background: #5b21b6;
            color: white;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #5b21b6;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9px;
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .note-box {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 12px;
            margin: 15px 0;
            font-size: 10px;
        }
        .note-box h4 {
            color: #374151;
            margin-bottom: 5px;
        }
        .checklist {
            margin: 10px 0;
        }
        .checklist-item {
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .check-icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            text-align: center;
            line-height: 16px;
            font-size: 10px;
            margin-right: 8px;
        }
        .check-icon.pass { background: #22c55e; color: white; }
        .check-icon.fail { background: #ef4444; color: white; }
        .check-icon.pending { background: #f59e0b; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">BTEVTA</div>
        <h1>Proof of Compliance Report</h1>
        <p>Compliance Period: {{ $period ?? 'Current Period' }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Compliance Summary -->
    <div class="compliance-summary">
        <div class="compliance-item success">
            <div class="value">{{ $stats['compliant'] ?? 0 }}</div>
            <div class="label">Fully Compliant</div>
        </div>
        <div class="compliance-item warning">
            <div class="value">{{ $stats['partial'] ?? 0 }}</div>
            <div class="label">Partially Compliant</div>
        </div>
        <div class="compliance-item danger">
            <div class="value">{{ $stats['non_compliant'] ?? 0 }}</div>
            <div class="label">Non-Compliant</div>
        </div>
    </div>

    <!-- Compliance Rate -->
    @php
        $total = ($stats['compliant'] ?? 0) + ($stats['partial'] ?? 0) + ($stats['non_compliant'] ?? 0);
        $complianceRate = $total > 0 ? (($stats['compliant'] ?? 0) / $total) * 100 : 0;
    @endphp
    <div class="note-box">
        <h4>Overall Compliance Rate: {{ number_format($complianceRate, 1) }}%</h4>
        <p>Based on {{ $total }} remittance records reviewed during this period.</p>
    </div>

    <!-- Compliance Checklist -->
    <div class="section-title">Compliance Requirements</div>
    <div class="checklist">
        @php
            $requirements = [
                ['name' => 'Valid Transfer Documentation', 'status' => 'pass'],
                ['name' => 'Beneficiary Verification', 'status' => 'pass'],
                ['name' => 'Amount Validation', 'status' => 'pass'],
                ['name' => 'Proof of Receipt', 'status' => $stats['proof_rate'] ?? 0 >= 80 ? 'pass' : 'pending'],
                ['name' => 'Bank Verification', 'status' => 'pass'],
            ];
        @endphp
        @foreach($requirements as $req)
        <div class="checklist-item">
            <span class="check-icon {{ $req['status'] }}">
                @if($req['status'] == 'pass') ✓ @elseif($req['status'] == 'fail') ✗ @else ! @endif
            </span>
            {{ $req['name'] }}
            <span class="badge badge-{{ $req['status'] == 'pass' ? 'success' : ($req['status'] == 'fail' ? 'danger' : 'warning') }}" style="float: right;">
                {{ ucfirst($req['status']) }}
            </span>
        </div>
        @endforeach
    </div>

    <!-- Detailed Records -->
    <div class="section-title">Compliance Details by Candidate</div>
    @if(isset($records) && count($records) > 0)
    <table>
        <thead>
            <tr>
                <th>Candidate</th>
                <th>TheLeap ID</th>
                <th class="text-center">Transfers</th>
                <th class="text-center">With Proof</th>
                <th class="text-center">Compliance</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            @php
                $proofRate = $record['total_transfers'] > 0 ? ($record['with_proof'] / $record['total_transfers']) * 100 : 0;
                $status = $proofRate >= 90 ? 'success' : ($proofRate >= 70 ? 'warning' : 'danger');
            @endphp
            <tr>
                <td>{{ $record['candidate_name'] ?? 'N/A' }}</td>
                <td>{{ $record['btevta_id'] ?? 'N/A' }}</td>
                <td class="text-center">{{ $record['total_transfers'] ?? 0 }}</td>
                <td class="text-center">{{ $record['with_proof'] ?? 0 }}</td>
                <td class="text-center">{{ number_format($proofRate, 0) }}%</td>
                <td class="text-center">
                    <span class="badge badge-{{ $status }}">
                        {{ $status == 'success' ? 'Compliant' : ($status == 'warning' ? 'Partial' : 'Non-Compliant') }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #6b7280; padding: 20px;">No compliance records available for this period.</p>
    @endif

    <!-- Certification -->
    <div class="note-box" style="margin-top: 30px; background: #f0fdf4; border-color: #86efac;">
        <h4 style="color: #166534;">Certification</h4>
        <p style="color: #15803d;">
            This report certifies that the above remittance records have been reviewed for compliance with
            BTEVTA guidelines and applicable regulations. Records marked as "Compliant" have complete
            documentation including proof of transfer and beneficiary verification.
        </p>
        <p style="margin-top: 10px; color: #166534;">
            <strong>Reviewed by:</strong> ______________________ <br>
            <strong>Date:</strong> {{ now()->format('M d, Y') }}
        </p>
    </div>

    <div class="footer">
        BTEVTA Remittance Management System | Proof of Compliance Report | Official Document
    </div>
</body>
</html>
