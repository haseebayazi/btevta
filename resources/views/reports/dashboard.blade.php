@extends('layouts.app')

@section('title', 'Remittance Reports & Analytics')

@push('styles')
<style>
    .report-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .chart-container {
        position: relative;
        height: 400px;
        margin: 20px 0;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-chart-line"></i> Remittance Analytics Dashboard</h2>
            <p class="text-muted">Comprehensive remittance reports and visualizations</p>
        </div>
        <div>
            <a href="{{ route('remittances.reports.export', ['type' => 'dashboard', 'format' => 'csv']) }}"
               class="btn btn-success">
                <i class="fas fa-download"></i> Export Data
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">Total Remittances</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_remittances'] ?? 0) }}</h2>
                        <p class="mb-0 mt-2">PKR {{ number_format($stats['total_amount'] ?? 0, 0) }}</p>
                    </div>
                    <div><i class="fas fa-money-bill-wave fa-3x opacity-50"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">This Year</h6>
                        <h2 class="mb-0">{{ number_format($stats['current_year_count'] ?? 0) }}</h2>
                        <p class="mb-0 mt-2">PKR {{ number_format($stats['current_year_amount'] ?? 0, 0) }}</p>
                    </div>
                    <div><i class="fas fa-calendar fa-3x opacity-50"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">Average Amount</h6>
                        <h2 class="mb-0">PKR {{ number_format($stats['average_amount'] ?? 0, 0) }}</h2>
                        <p class="mb-0 mt-2">Per remittance</p>
                    </div>
                    <div><i class="fas fa-chart-bar fa-3x opacity-50"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 opacity-75">Total Candidates</h6>
                        <h2 class="mb-0">{{ number_format($stats['total_candidates'] ?? 0) }}</h2>
                        <p class="mb-0 mt-2">Active remitters</p>
                    </div>
                    <div><i class="fas fa-users fa-3x opacity-50"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trends Chart -->
    <div class="card report-card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Remittance Trends</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Purpose Breakdown and Transfer Methods -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card report-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-pie-chart"></i> Purpose Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="purposeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card report-card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Transfer Methods</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="transferMethodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Country Analysis and Top Remitters -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card report-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-globe"></i> Country Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="countryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card report-card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Remitters</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Candidate</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCandidates ?? [] as $index => $candidate)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('candidates.profile', $candidate['candidate_id']) }}">
                                            {{ $candidate['candidate_name'] }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ $candidate['total_count'] }}</td>
                                    <td class="text-end">PKR {{ number_format($candidate['total_amount'], 0) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Proof Compliance -->
    <div class="card report-card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Proof Compliance Status</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="complianceChart"></canvas>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>With Proof</th>
                                <td class="text-success fw-bold">{{ number_format($stats['with_proof'] ?? 0) }}</td>
                                <td class="text-success">{{ number_format($stats['proof_compliance_rate'] ?? 0, 1) }}%</td>
                            </tr>
                            <tr>
                                <th>Without Proof</th>
                                <td class="text-danger fw-bold">{{ number_format($stats['without_proof'] ?? 0) }}</td>
                                <td class="text-danger">{{ number_format(100 - ($stats['proof_compliance_rate'] ?? 0), 1) }}%</td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td class="fw-bold">{{ number_format(($stats['with_proof'] ?? 0) + ($stats['without_proof'] ?? 0)) }}</td>
                                <td>100%</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Monthly Trends Line Chart
const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($monthlyTrends ?? [], 'month_name')) !!},
        datasets: [
            {
                label: 'Count',
                data: {!! json_encode(array_column($monthlyTrends ?? [], 'count')) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                yAxisID: 'y',
            },
            {
                label: 'Amount (PKR)',
                data: {!! json_encode(array_column($monthlyTrends ?? [], 'amount')) !!},
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Count'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Amount (PKR)'
                },
                grid: {
                    drawOnChartArea: false,
                },
            },
        }
    }
});

// Purpose Pie Chart
const purposeCtx = document.getElementById('purposeChart').getContext('2d');
new Chart(purposeCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_column($purposeAnalysis ?? [], 'purpose')) !!},
        datasets: [{
            data: {!! json_encode(array_column($purposeAnalysis ?? [], 'count')) !!},
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
            ],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Transfer Method Pie Chart
const transferCtx = document.getElementById('transferMethodChart').getContext('2d');
new Chart(transferCtx, {
    type: 'pie',
    data: {
        labels: {!! json_encode(array_column($transferMethods ?? [], 'method')) !!},
        datasets: [{
            data: {!! json_encode(array_column($transferMethods ?? [], 'count')) !!},
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 99, 132, 0.8)',
            ],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});

// Country Horizontal Bar Chart
const countryCtx = document.getElementById('countryChart').getContext('2d');
new Chart(countryCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode(array_column($countryAnalysis ?? [], 'country')) !!},
        datasets: [{
            label: 'Remittances',
            data: {!! json_encode(array_column($countryAnalysis ?? [], 'count')) !!},
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Proof Compliance Doughnut Chart
const complianceCtx = document.getElementById('complianceChart').getContext('2d');
new Chart(complianceCtx, {
    type: 'doughnut',
    data: {
        labels: ['With Proof', 'Without Proof'],
        datasets: [{
            data: [{{ $stats['with_proof'] ?? 0 }}, {{ $stats['without_proof'] ?? 0 }}],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
            ],
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
            }
        }
    }
});
</script>
@endpush
