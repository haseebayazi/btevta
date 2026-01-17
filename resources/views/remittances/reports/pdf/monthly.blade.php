<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Remittance Report - {{ $year }}</title>
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
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .summary-item .label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
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
        tr:hover {
            background: #f3f4f6;
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
        .quarterly-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .quarter {
            display: table-cell;
            width: 25%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .quarter h4 {
            font-size: 12px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .tfoot td {
            background: #1e40af;
            color: white;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 3px;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">TheLeap</div>
        <h1>Monthly Remittance Report</h1>
        <p>Year: {{ $year }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Summary Stats -->
    @php
        $yearlyTotal = collect($monthlyTrends)->sum('total_amount');
        $yearlyCount = collect($monthlyTrends)->sum('count');
        $yearlyAvg = $yearlyCount > 0 ? $yearlyTotal / $yearlyCount : 0;
        $peakMonth = collect($monthlyTrends)->sortByDesc('total_amount')->first();
    @endphp

    <div class="summary-grid">
        <div class="summary-item">
            <div class="value">{{ number_format($yearlyTotal, 2) }}</div>
            <div class="label">Yearly Total (PKR)</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ number_format($yearlyCount) }}</div>
            <div class="label">Total Transfers</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ number_format($yearlyAvg, 2) }}</div>
            <div class="label">Average Transfer (PKR)</div>
        </div>
        <div class="summary-item">
            <div class="value">{{ $peakMonth['month'] ?? 'N/A' }}</div>
            <div class="label">Peak Month</div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="section-title">Monthly Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Count</th>
                <th class="text-right">Total Amount (PKR)</th>
                <th class="text-right">Avg Amount (PKR)</th>
                <th class="text-center">% of Year</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyTrends as $trend)
            @php
                $percentOfTotal = $yearlyTotal > 0 ? ($trend['total_amount'] / $yearlyTotal) * 100 : 0;
            @endphp
            <tr>
                <td>{{ $trend['month'] }}</td>
                <td class="text-right">{{ number_format($trend['count']) }}</td>
                <td class="text-right">{{ number_format($trend['total_amount'], 2) }}</td>
                <td class="text-right">{{ number_format($trend['avg_amount'], 2) }}</td>
                <td class="text-center">{{ number_format($percentOfTotal, 1) }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot">
                <td>TOTAL</td>
                <td class="text-right">{{ number_format($yearlyCount) }}</td>
                <td class="text-right">{{ number_format($yearlyTotal, 2) }}</td>
                <td class="text-right">{{ number_format($yearlyAvg, 2) }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Quarterly Analysis -->
    <div class="section-title">Quarterly Analysis</div>
    <div class="quarterly-grid">
        @php
            $quarters = [
                'Q1' => [1, 2, 3],
                'Q2' => [4, 5, 6],
                'Q3' => [7, 8, 9],
                'Q4' => [10, 11, 12],
            ];
        @endphp

        @foreach($quarters as $quarterName => $months)
            @php
                $quarterData = collect($monthlyTrends)->filter(function($trend) use ($months) {
                    return in_array($trend['month_number'], $months);
                });
                $qCount = $quarterData->sum('count');
                $qTotal = $quarterData->sum('total_amount');
            @endphp
            <div class="quarter">
                <h4>{{ $quarterName }}</h4>
                <p><strong>{{ number_format($qCount) }}</strong> transfers</p>
                <p>PKR {{ number_format($qTotal, 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="footer">
        TheLeap Remittance Management System | Page 1 | Confidential
    </div>
</body>
</html>
