@extends('layouts.app')

@section('title', $training->title)

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header with Actions -->
    <div class="flex flex-wrap justify-between items-start mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $training->title }}</h1>
            <p class="text-gray-600 mt-2">
                <span class="inline-flex items-center">
                    <i class="fas fa-users mr-2"></i>Batch: {{ $training->batch_name }}
                </span>
                <span class="mx-3">|</span>
                <span class="inline-flex items-center">
                    <i class="fas fa-calendar mr-2"></i>{{ $training->start_date->format('M d') }} - {{ $training->end_date->format('M d, Y') }}
                </span>
            </p>
        </div>
        
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('training.attendance-form', $training) }}" class="btn btn-success">
                <i class="fas fa-clipboard-check mr-2"></i>Mark Attendance
            </a>
            <a href="{{ route('training.assessment-view', $training) }}" class="btn btn-primary">
                <i class="fas fa-chart-line mr-2"></i>Assessment
            </a>
            <a href="{{ route('training.edit', $training) }}" class="btn btn-secondary">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-gradient-to-br from-blue-500 to-blue-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Candidates</p>
                    <h3 class="text-3xl font-bold mt-1">{{ $training->candidates()->count() }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="card bg-gradient-to-br from-green-500 to-green-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Avg Attendance</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($avgAttendance ?? 0, 1) }}%</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-clipboard-check text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="card bg-gradient-to-br from-purple-500 to-purple-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Pass Rate</p>
                    <h3 class="text-3xl font-bold mt-1">{{ number_format($passRate ?? 0, 1) }}%</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-graduation-cap text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="card bg-gradient-to-br from-orange-500 to-orange-600 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Duration</p>
                    <h3 class="text-3xl font-bold mt-1">{{ $training->duration_days }} days</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Training Details -->
            <div class="card">
                <h2 class="text-xl font-bold mb-4">Training Details</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Program</label>
                        <p class="text-gray-900 font-medium">{{ $training->program }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Campus</label>
                        <p class="text-gray-900 font-medium">{{ $training->campus->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Instructor</label>
                        <p class="text-gray-900 font-medium">{{ $training->instructor->name ?? 'Not assigned' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <p>
                            <span class="badge badge-{{ $training->status_color }}">
                                {{ ucfirst($training->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                @if($training->description)
                <div class="mt-4 pt-4 border-t">
                    <label class="text-sm font-medium text-gray-600">Description</label>
                    <p class="text-gray-700 mt-1">{{ $training->description }}</p>
                </div>
                @endif
            </div>

            <!-- Attendance History -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Attendance History</h2>
                    <a href="{{ route('training.attendance-report', ['training_id' => $training->id]) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View Full Report <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                @if($attendanceRecords->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Present</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Absent</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Late</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($attendanceRecords->take(5) as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap font-medium">
                                    {{ $record->attendance_date->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-green-600 font-semibold">{{ $record->present_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-red-600 font-semibold">{{ $record->absent_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-yellow-600 font-semibold">{{ $record->late_count }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="badge badge-{{ $record->attendance_percentage >= 80 ? 'success' : ($record->attendance_percentage >= 60 ? 'warning' : 'danger') }}">
                                        {{ number_format($record->attendance_percentage, 1) }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-3 text-gray-400"></i>
                    <p>No attendance records yet</p>
                    <a href="{{ route('training.attendance-form', $training) }}" class="text-blue-600 hover:text-blue-700 mt-2 inline-block">
                        Mark First Attendance
                    </a>
                </div>
                @endif
            </div>

            <!-- Assessment History -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Assessment History</h2>
                    <a href="{{ route('training.assessment-report', ['training_id' => $training->id]) }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View Full Report <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                @if($assessments->count() > 0)
                <div class="space-y-3">
                    @foreach($assessments->take(5) as $assessment)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    {{ ucfirst($assessment->assessment_type) }} Assessment
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ $assessment->assessment_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ number_format($assessment->average_marks, 1) }}/{{ $assessment->max_marks }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Pass Rate: <span class="font-semibold">{{ number_format($assessment->pass_rate, 1) }}%</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-chart-line text-4xl mb-3 text-gray-400"></i>
                    <p>No assessments conducted yet</p>
                    <a href="{{ route('training.assessment-view', $training) }}" class="text-blue-600 hover:text-blue-700 mt-2 inline-block">
                        Conduct First Assessment
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Quick Actions -->
            <div class="card">
                <h3 class="font-bold text-lg mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('training.attendance-form', $training) }}" 
                       class="block px-4 py-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition">
                        <i class="fas fa-clipboard-check mr-2"></i>Mark Attendance
                    </a>
                    <a href="{{ route('training.assessment-view', $training) }}" 
                       class="block px-4 py-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition">
                        <i class="fas fa-chart-line mr-2"></i>Conduct Assessment
                    </a>
                    <a href="{{ route('training.batch-performance', $training) }}" 
                       class="block px-4 py-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg transition">
                        <i class="fas fa-analytics mr-2"></i>View Performance
                    </a>
                    <a href="{{ route('candidates.index', ['training_id' => $training->id]) }}" 
                       class="block px-4 py-3 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg transition">
                        <i class="fas fa-users mr-2"></i>Manage Candidates
                    </a>
                </div>
            </div>

            <!-- Enrolled Candidates -->
            <div class="card">
                <h3 class="font-bold text-lg mb-4">Enrolled Candidates ({{ $training->candidates->count() }})</h3>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($training->candidates->take(10) as $candidate)
                    <div class="flex items-center space-x-3">
                        <img src="{{ $candidate->photo_url ?? asset('img/default.png') }}" 
                             class="w-10 h-10 rounded-full">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $candidate->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $candidate->passport_number }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-sm text-center py-4">No candidates enrolled</p>
                    @endforelse
                    
                    @if($training->candidates->count() > 10)
                    <a href="{{ route('candidates.index', ['training_id' => $training->id]) }}" 
                       class="block text-center text-blue-600 hover:text-blue-700 text-sm font-medium pt-2 border-t">
                        View All {{ $training->candidates->count() }} Candidates
                    </a>
                    @endif
                </div>
            </div>

            <!-- Certificates -->
            @if($training->status == 'completed')
            <div class="card bg-gradient-to-br from-yellow-50 to-orange-50">
                <div class="text-center">
                    <i class="fas fa-certificate text-4xl text-yellow-600 mb-3"></i>
                    <h3 class="font-bold text-lg mb-2">Certificates</h3>
                    <p class="text-sm text-gray-600 mb-4">Training completed. Issue certificates to candidates.</p>
                    <button class="btn btn-warning w-full">
                        <i class="fas fa-file-certificate mr-2"></i>Generate Certificates
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
