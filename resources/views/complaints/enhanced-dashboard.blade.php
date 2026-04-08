@extends('layouts.app')
@section('title', 'Enhanced Complaints Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-chart-line text-blue-500 mr-2"></i>Enhanced Complaints Dashboard
            </h1>
            <p class="text-gray-500 text-sm mt-1">Module 9B — Metrics, trends, templates &amp; evidence analytics</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('complaints.templates') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm flex items-center gap-2">
                <i class="fas fa-clipboard-list"></i> Templates
            </a>
            <a href="{{ route('complaints.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm flex items-center gap-2">
                <i class="fas fa-list"></i> All Complaints
            </a>
        </div>
    </div>


    {{-- KPI Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border shadow-sm p-5 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $dashboard['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Complaints</p>
        </div>
        <div class="bg-white rounded-lg border shadow-sm p-5 text-center">
            <p class="text-3xl font-bold text-red-600">{{ $dashboard['overdue_count'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Overdue</p>
        </div>
        <div class="bg-white rounded-lg border shadow-sm p-5 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $dashboard['sla_compliance_rate'] ?? 0 }}%</p>
            <p class="text-xs text-gray-500 mt-1">SLA Compliance</p>
        </div>
        <div class="bg-white rounded-lg border shadow-sm p-5 text-center">
            <p class="text-3xl font-bold text-purple-600">
                {{ $dashboard['resolution_metrics']?->avg_resolution_hours ? round($dashboard['resolution_metrics']->avg_resolution_hours, 1) . 'h' : '—' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Avg Resolution Time</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- By Status --}}
        <div class="bg-white rounded-lg border shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4 text-sm">
                <i class="fas fa-layer-group text-blue-400 mr-2"></i>By Status
            </h2>
            <div class="space-y-3">
                @foreach($dashboard['by_status'] ?? [] as $status => $count)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 bg-gray-100 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full"
                                 style="width: {{ ($dashboard['total'] ?? 0) > 0 ? round(($count / $dashboard['total']) * 100) : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-8 text-right">{{ $count }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- By Category --}}
        <div class="bg-white rounded-lg border shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4 text-sm">
                <i class="fas fa-tags text-green-400 mr-2"></i>By Category
            </h2>
            <div class="space-y-3">
                @foreach($dashboard['by_category'] ?? [] as $category => $count)
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ ucfirst($category) }}</span>
                    <span class="text-sm font-semibold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Evidence by Category --}}
        <div class="bg-white rounded-lg border shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4 text-sm">
                <i class="fas fa-paperclip text-purple-400 mr-2"></i>Evidence by Category
            </h2>
            @if(($dashboard['evidence_by_category'] ?? collect())->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">No categorized evidence yet</p>
            @else
            <div class="space-y-3">
                @foreach($dashboard['evidence_by_category'] ?? [] as $category => $count)
                @php $catEnum = \App\Enums\ComplaintEvidenceCategory::tryFrom($category); @endphp
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 flex items-center gap-1">
                        @if($catEnum)<i class="{{ $catEnum->icon() }} text-xs text-gray-400"></i>@endif
                        {{ $catEnum?->label() ?? ucfirst($category) }}
                    </span>
                    <span class="text-sm font-semibold text-gray-700 bg-purple-50 text-purple-700 px-2 py-0.5 rounded">{{ $count }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Resolution Time Metrics --}}
        @if($dashboard['resolution_metrics'] ?? null)
        <div class="bg-white rounded-lg border shadow-sm p-5">
            <h2 class="font-semibold text-gray-700 mb-4 text-sm">
                <i class="fas fa-clock text-orange-400 mr-2"></i>Resolution Time
            </h2>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-xl font-bold text-green-600">
                        {{ $dashboard['resolution_metrics']->min_resolution_hours ? round($dashboard['resolution_metrics']->min_resolution_hours) . 'h' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500">Fastest</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-blue-600">
                        {{ $dashboard['resolution_metrics']->avg_resolution_hours ? round($dashboard['resolution_metrics']->avg_resolution_hours, 1) . 'h' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500">Average</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-red-600">
                        {{ $dashboard['resolution_metrics']->max_resolution_hours ? round($dashboard['resolution_metrics']->max_resolution_hours) . 'h' : '—' }}
                    </p>
                    <p class="text-xs text-gray-500">Slowest</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Templates Quick Access --}}
        <div class="bg-white rounded-lg border shadow-sm p-5 lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-semibold text-gray-700 text-sm">
                    <i class="fas fa-clipboard-list text-indigo-400 mr-2"></i>Complaint Templates
                </h2>
                <a href="{{ route('complaints.templates') }}"
                   class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            @if(($dashboard['templates'] ?? collect())->isEmpty())
            <p class="text-sm text-gray-400 text-center py-4">No templates configured</p>
            @else
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach(($dashboard['templates'] ?? collect())->take(4) as $template)
                <div class="border rounded-lg p-3 hover:bg-gray-50 transition-colors">
                    <div class="flex justify-between items-start mb-1">
                        <p class="font-medium text-gray-800 text-sm">{{ $template->name }}</p>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-{{ $template->default_priority?->color() ?? 'gray' }}-100 text-{{ $template->default_priority?->color() ?? 'gray' }}-700">
                            {{ $template->default_priority?->label() ?? $template->default_priority }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">{{ ucfirst($template->category) }}</p>
                    @if($template->suggested_sla_hours)
                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fas fa-clock mr-1"></i>{{ $template->suggested_sla_hours }}h SLA
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Category Trends --}}
        @if(($dashboard['category_trends'] ?? collect())->isNotEmpty())
        <div class="bg-white rounded-lg border shadow-sm p-5 lg:col-span-3">
            <h2 class="font-semibold text-gray-700 mb-4 text-sm">
                <i class="fas fa-chart-bar text-teal-400 mr-2"></i>Category Trends (Last 6 Months)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 pr-4 text-gray-500 font-medium">Category</th>
                            @php
                                $months = collect();
                                foreach (range(5, 0) as $i) {
                                    $months->push(now()->subMonths($i)->format('Y-m'));
                                }
                            @endphp
                            @foreach($months as $month)
                            <th class="text-center py-2 px-2 text-gray-500 font-medium">
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M') }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($dashboard['category_trends'] as $category => $trends)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 pr-4 font-medium text-gray-700">{{ ucfirst($category) }}</td>
                            @foreach($months as $month)
                            @php $count = $trends->where('month', $month)->first()?->count ?? 0; @endphp
                            <td class="text-center py-2 px-2">
                                @if($count > 0)
                                <span class="inline-block w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-semibold text-xs leading-6 text-center">{{ $count }}</span>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
