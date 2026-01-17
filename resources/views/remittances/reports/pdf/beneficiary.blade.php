<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Beneficiary Remittance Report</title>
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
            border-bottom: 2px solid #3b82f6;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .summary-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-box h3 {
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .summary-row {
            display: table;
            width: 100%;
        }
        .summary-cell {
            display: table-cell;
            width: 33%;
            padding: 5px 0;
        }
        .summary-cell .label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
        }
        .summary-cell .value {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
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
            background: #1e40af;
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
            color: #1e40af;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 3px;
        }
        .badge-verified { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">TheLeap</div>
        <h1>Beneficiary Remittance Report</h1>
        <p>Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    @if(isset($beneficiary))
    <!-- Beneficiary Summary -->
    <div class="summary-box">
        <h3>Beneficiary Information</h3>
        <div class="summary-row">
            <div class="summary-cell">
                <div class="label">Beneficiary Name</div>
                <div class="value">{{ $beneficiary->name ?? 'N/A' }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Relationship</div>
                <div class="value">{{ $beneficiary->relationship ?? 'N/A' }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Status</div>
                <div class="value">{{ $beneficiary->is_verified ? 'Verified' : 'Pending' }}</div>
            </div>
        </div>
        <div class="summary-row">
            <div class="summary-cell">
                <div class="label">CNIC</div>
                <div class="value">{{ $beneficiary->cnic ?? 'N/A' }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Account Number</div>
                <div class="value">{{ $beneficiary->account_number ?? 'N/A' }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Bank</div>
                <div class="value">{{ $beneficiary->bank_name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics -->
    @if(isset($stats))
    <div class="summary-box">
        <h3>Transfer Statistics</h3>
        <div class="summary-row">
            <div class="summary-cell">
                <div class="label">Total Transfers</div>
                <div class="value">{{ number_format($stats['total_count'] ?? 0) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Total Amount</div>
                <div class="value">PKR {{ number_format($stats['total_amount'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Average Transfer</div>
                <div class="value">PKR {{ number_format($stats['avg_amount'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Transfer History -->
    <div class="section-title">Transfer History</div>
    @if(isset($remittances) && count($remittances) > 0)
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Candidate</th>
                <th class="text-right">Amount (PKR)</th>
                <th>Purpose</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($remittances as $remittance)
            <tr>
                <td>{{ $remittance->transfer_date?->format('M d, Y') ?? 'N/A' }}</td>
                <td>{{ $remittance->reference_number ?? 'N/A' }}</td>
                <td>{{ $remittance->candidate->name ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($remittance->amount, 2) }}</td>
                <td>{{ ucfirst($remittance->purpose ?? 'N/A') }}</td>
                <td class="text-center">
                    <span class="badge badge-{{ $remittance->status == 'completed' ? 'verified' : 'pending' }}">
                        {{ ucfirst($remittance->status ?? 'Pending') }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #6b7280; padding: 20px;">No transfer records found.</p>
    @endif

    <div class="footer">
        TheLeap Remittance Management System | Confidential Report
    </div>
</body>
</html>
