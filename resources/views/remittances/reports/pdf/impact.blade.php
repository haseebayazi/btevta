<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Remittance Impact Report</title>
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
            border-bottom: 2px solid #10b981;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #065f46;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 10px;
        }
        .impact-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .impact-card h2 {
            font-size: 28px;
            color: #065f46;
            margin-bottom: 5px;
        }
        .impact-card p {
            color: #047857;
            font-size: 12px;
        }
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .metric {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .metric .value {
            font-size: 18px;
            font-weight: bold;
            color: #065f46;
        }
        .metric .label {
            font-size: 9px;
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
            background: #065f46;
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
            color: #065f46;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1fae5;
        }
        .highlight-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 12px 15px;
            margin: 15px 0;
        }
        .highlight-box h4 {
            color: #92400e;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .highlight-box p {
            color: #78350f;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">BTEVTA</div>
        <h1>Remittance Impact Report</h1>
        <p>Analysis Period: {{ $period ?? 'All Time' }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Total Impact -->
    <div class="impact-card">
        <h2>PKR {{ number_format($stats['total_remitted'] ?? 0, 0) }}</h2>
        <p>Total Remittances Sent Home by BTEVTA Candidates</p>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric">
            <div class="value">{{ number_format($stats['total_candidates'] ?? 0) }}</div>
            <div class="label">Candidates Sending</div>
        </div>
        <div class="metric">
            <div class="value">{{ number_format($stats['total_beneficiaries'] ?? 0) }}</div>
            <div class="label">Families Supported</div>
        </div>
        <div class="metric">
            <div class="value">{{ number_format($stats['total_transfers'] ?? 0) }}</div>
            <div class="label">Total Transfers</div>
        </div>
        <div class="metric">
            <div class="value">PKR {{ number_format($stats['avg_monthly'] ?? 0, 0) }}</div>
            <div class="label">Avg Monthly per Candidate</div>
        </div>
    </div>

    <!-- Impact by Trade -->
    <div class="section-title">Impact by Trade</div>
    @if(isset($byTrade) && count($byTrade) > 0)
    <table>
        <thead>
            <tr>
                <th>Trade</th>
                <th class="text-center">Candidates</th>
                <th class="text-right">Total Amount (PKR)</th>
                <th class="text-right">Avg per Candidate</th>
                <th class="text-center">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = collect($byTrade)->sum('total_amount'); @endphp
            @foreach($byTrade as $trade)
            <tr>
                <td>{{ $trade['trade_name'] ?? 'N/A' }}</td>
                <td class="text-center">{{ number_format($trade['candidate_count'] ?? 0) }}</td>
                <td class="text-right">{{ number_format($trade['total_amount'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($trade['avg_per_candidate'] ?? 0, 2) }}</td>
                <td class="text-center">
                    {{ $grandTotal > 0 ? number_format(($trade['total_amount'] / $grandTotal) * 100, 1) : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #6b7280; padding: 20px;">No trade-wise data available.</p>
    @endif

    <!-- Impact by Region -->
    <div class="section-title">Impact by Region</div>
    @if(isset($byRegion) && count($byRegion) > 0)
    <table>
        <thead>
            <tr>
                <th>Region/Campus</th>
                <th class="text-center">Beneficiaries</th>
                <th class="text-right">Total Received (PKR)</th>
                <th class="text-center">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byRegion as $region)
            <tr>
                <td>{{ $region['region_name'] ?? 'N/A' }}</td>
                <td class="text-center">{{ number_format($region['beneficiary_count'] ?? 0) }}</td>
                <td class="text-right">{{ number_format($region['total_amount'] ?? 0, 2) }}</td>
                <td class="text-center">
                    {{ $grandTotal > 0 ? number_format(($region['total_amount'] / $grandTotal) * 100, 1) : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; color: #6b7280; padding: 20px;">No regional data available.</p>
    @endif

    <!-- Key Insights -->
    <div class="highlight-box">
        <h4>Key Insights</h4>
        <p>
            BTEVTA trained workers have contributed significantly to the national economy through foreign remittances.
            These funds support {{ number_format($stats['total_beneficiaries'] ?? 0) }} families across Pakistan,
            providing essential financial support for education, healthcare, and household expenses.
        </p>
    </div>

    <div class="footer">
        BTEVTA Remittance Management System | Impact Assessment Report | Confidential
    </div>
</body>
</html>
