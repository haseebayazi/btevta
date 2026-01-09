<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Remittance Purpose Analysis Report</title>
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
            border-bottom: 2px solid #0891b2;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #0e7490;
            margin-bottom: 5px;
        }
        .header p {
            color: #6b7280;
            font-size: 12px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0e7490;
            margin-bottom: 10px;
        }
        .purpose-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .purpose-card {
            display: table-cell;
            width: 33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .purpose-card .icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .purpose-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #0e7490;
        }
        .purpose-card .label {
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
            background: #0e7490;
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
            color: #0e7490;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .progress-bar {
            height: 12px;
            background: #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0891b2, #06b6d4);
        }
        .insight-box {
            background: #ecfeff;
            border: 1px solid #67e8f9;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .insight-box h4 {
            color: #0e7490;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .insight-box ul {
            margin-left: 15px;
            color: #155e75;
        }
        .insight-box li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">BTEVTA</div>
        <h1>Remittance Purpose Analysis</h1>
        <p>Analysis Period: {{ $period ?? 'All Time' }} | Generated: {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <!-- Purpose Summary -->
    @php
        $purposes = $purposeData ?? [
            ['purpose' => 'Family Support', 'count' => 0, 'amount' => 0],
            ['purpose' => 'Education', 'count' => 0, 'amount' => 0],
            ['purpose' => 'Healthcare', 'count' => 0, 'amount' => 0],
            ['purpose' => 'Investment', 'count' => 0, 'amount' => 0],
            ['purpose' => 'Other', 'count' => 0, 'amount' => 0],
        ];
        $totalAmount = collect($purposes)->sum('amount');
        $totalCount = collect($purposes)->sum('count');
        $topPurpose = collect($purposes)->sortByDesc('amount')->first();
    @endphp

    <div class="purpose-grid">
        <div class="purpose-card">
            <div class="value">PKR {{ number_format($totalAmount, 0) }}</div>
            <div class="label">Total Remitted</div>
        </div>
        <div class="purpose-card">
            <div class="value">{{ number_format($totalCount) }}</div>
            <div class="label">Total Transfers</div>
        </div>
        <div class="purpose-card">
            <div class="value">{{ $topPurpose['purpose'] ?? 'N/A' }}</div>
            <div class="label">Top Purpose</div>
        </div>
    </div>

    <!-- Purpose Breakdown -->
    <div class="section-title">Purpose Breakdown</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Purpose</th>
                <th class="text-center">Transfers</th>
                <th class="text-right">Amount (PKR)</th>
                <th class="text-center">% of Total</th>
                <th style="width: 25%;">Distribution</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purposes as $purpose)
            @php
                $percentage = $totalAmount > 0 ? ($purpose['amount'] / $totalAmount) * 100 : 0;
            @endphp
            <tr>
                <td><strong>{{ $purpose['purpose'] }}</strong></td>
                <td class="text-center">{{ number_format($purpose['count']) }}</td>
                <td class="text-right">{{ number_format($purpose['amount'], 2) }}</td>
                <td class="text-center">{{ number_format($percentage, 1) }}%</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: {{ $percentage }}%"></div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Trend by Purpose -->
    @if(isset($monthlyByPurpose) && count($monthlyByPurpose) > 0)
    <div class="section-title">Monthly Trend by Purpose</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Family Support</th>
                <th class="text-right">Education</th>
                <th class="text-right">Healthcare</th>
                <th class="text-right">Investment</th>
                <th class="text-right">Other</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyByPurpose as $month => $data)
            <tr>
                <td>{{ $month }}</td>
                <td class="text-right">{{ number_format($data['family_support'] ?? 0, 0) }}</td>
                <td class="text-right">{{ number_format($data['education'] ?? 0, 0) }}</td>
                <td class="text-right">{{ number_format($data['healthcare'] ?? 0, 0) }}</td>
                <td class="text-right">{{ number_format($data['investment'] ?? 0, 0) }}</td>
                <td class="text-right">{{ number_format($data['other'] ?? 0, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Insights -->
    <div class="insight-box">
        <h4>Key Insights</h4>
        <ul>
            <li><strong>Primary Use:</strong> {{ $topPurpose['purpose'] ?? 'Family Support' }} accounts for the majority of remittances</li>
            <li><strong>Family Focus:</strong> Most workers prioritize supporting their families back home</li>
            <li><strong>Education Investment:</strong> Significant portion allocated for children's education</li>
            <li><strong>Healthcare:</strong> Medical expenses remain a consistent remittance purpose</li>
        </ul>
    </div>

    <!-- Recommendations -->
    <div class="section-title">Recommendations</div>
    <div style="background: #f9fafb; padding: 15px; border: 1px solid #e5e7eb;">
        <ol style="margin-left: 15px; color: #374151;">
            <li style="margin: 8px 0;">Continue monitoring purpose distribution to understand worker priorities</li>
            <li style="margin: 8px 0;">Consider financial literacy programs for investment-related remittances</li>
            <li style="margin: 8px 0;">Partner with healthcare providers for medical expense management</li>
            <li style="margin: 8px 0;">Explore education savings schemes for workers' children</li>
        </ol>
    </div>

    <div class="footer">
        BTEVTA Remittance Management System | Purpose Analysis Report | Confidential
    </div>
</body>
</html>
