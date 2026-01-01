<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\CampusEquipment;
use App\Models\EquipmentUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CampusEquipment::class);

        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;

        $query = CampusEquipment::with(['campus', 'creator'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter));

        if ($request->filled('campus_id') && !$campusFilter) {
            $query->where('campus_id', $request->campus_id);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        $equipment = $query->latest()->paginate(20);

        $campuses = $campusFilter ? [] : Campus::where('is_active', true)->pluck('name', 'id');
        $categories = CampusEquipment::CATEGORIES;
        $statuses = CampusEquipment::STATUSES;
        $conditions = CampusEquipment::CONDITIONS;

        // Summary stats
        $statsQuery = CampusEquipment::when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter));
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'available' => (clone $statsQuery)->where('status', 'available')->count(),
            'in_use' => (clone $statsQuery)->where('status', 'in_use')->count(),
            'maintenance' => (clone $statsQuery)->where('status', 'maintenance')->count(),
            'needs_maintenance' => (clone $statsQuery)->needsMaintenance()->count(),
            'total_value' => (clone $statsQuery)->sum('current_value'),
        ];

        return view('equipment.index', compact('equipment', 'campuses', 'categories', 'statuses', 'conditions', 'stats'));
    }

    public function create()
    {
        $this->authorize('create', CampusEquipment::class);

        $user = auth()->user();
        $campuses = $user->role === 'campus_admin'
            ? Campus::where('id', $user->campus_id)->pluck('name', 'id')
            : Campus::where('is_active', true)->pluck('name', 'id');

        $categories = CampusEquipment::CATEGORIES;
        $conditions = CampusEquipment::CONDITIONS;

        return view('equipment.create', compact('campuses', 'categories', 'conditions'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CampusEquipment::class);

        $validated = $request->validate([
            'campus_id' => 'required|exists:campuses,id',
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(CampusEquipment::CATEGORIES)),
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'condition' => 'required|in:' . implode(',', array_keys(CampusEquipment::CONDITIONS)),
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $validated['equipment_code'] = CampusEquipment::generateEquipmentCode($validated['campus_id'], $validated['category']);
        $validated['status'] = 'available';
        $validated['created_by'] = auth()->id();

        $equipment = CampusEquipment::create($validated);

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Equipment added successfully.');
    }

    public function show(CampusEquipment $equipment)
    {
        $this->authorize('view', $equipment);

        $equipment->load(['campus', 'creator', 'updater', 'usageLogs' => function ($q) {
            $q->latest()->limit(10);
        }]);

        $utilizationRate = $equipment->getUtilizationRate();

        // Monthly usage stats
        $monthlyUsage = EquipmentUsageLog::where('equipment_id', $equipment->id)
            ->selectRaw('YEAR(start_time) as year, MONTH(start_time) as month, SUM(hours_used) as total_hours, COUNT(*) as sessions')
            ->groupBy('year', 'month')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(6)
            ->get();

        return view('equipment.show', compact('equipment', 'utilizationRate', 'monthlyUsage'));
    }

    public function edit(CampusEquipment $equipment)
    {
        $this->authorize('update', $equipment);

        $user = auth()->user();
        $campuses = $user->role === 'campus_admin'
            ? Campus::where('id', $user->campus_id)->pluck('name', 'id')
            : Campus::where('is_active', true)->pluck('name', 'id');

        $categories = CampusEquipment::CATEGORIES;
        $conditions = CampusEquipment::CONDITIONS;
        $statuses = CampusEquipment::STATUSES;

        return view('equipment.edit', compact('equipment', 'campuses', 'categories', 'conditions', 'statuses'));
    }

    public function update(Request $request, CampusEquipment $equipment)
    {
        $this->authorize('update', $equipment);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(CampusEquipment::CATEGORIES)),
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'condition' => 'required|in:' . implode(',', array_keys(CampusEquipment::CONDITIONS)),
            'status' => 'required|in:' . implode(',', array_keys(CampusEquipment::STATUSES)),
            'quantity' => 'required|integer|min:1',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();
        $equipment->update($validated);

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(CampusEquipment $equipment)
    {
        $this->authorize('delete', $equipment);

        $equipment->delete();
        return redirect()->route('equipment.index')
            ->with('success', 'Equipment deleted successfully.');
    }

    public function logUsage(Request $request, CampusEquipment $equipment)
    {
        $this->authorize('logUsage', $equipment);

        $validated = $request->validate([
            'batch_id' => 'nullable|exists:batches,id',
            'usage_type' => 'required|in:' . implode(',', array_keys(EquipmentUsageLog::USAGE_TYPES)),
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'students_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['equipment_id'] = $equipment->id;
        $validated['user_id'] = auth()->id();
        $validated['start_time'] = $validated['start_time'] ?? now();
        $validated['status'] = !empty($validated['end_time']) ? 'completed' : 'active';

        EquipmentUsageLog::create($validated);

        // Update equipment status
        if ($validated['usage_type'] === 'training' && !$validated['end_time']) {
            $equipment->update(['status' => 'in_use']);
        } elseif ($validated['usage_type'] === 'maintenance') {
            $equipment->update(['status' => 'maintenance']);
        }

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Usage logged successfully.');
    }

    public function endUsage(Request $request, CampusEquipment $equipment, EquipmentUsageLog $log)
    {
        $this->authorize('update', $log);

        if ($log->equipment_id !== $equipment->id) {
            abort(404);
        }

        $log->update([
            'end_time' => now(),
            'status' => 'completed',
        ]);

        // Reset equipment status to available if no other active sessions
        $hasActiveSessions = EquipmentUsageLog::where('equipment_id', $equipment->id)
            ->where('status', 'active')
            ->exists();

        if (!$hasActiveSessions) {
            $equipment->update(['status' => 'available']);
        }

        return redirect()->route('equipment.show', $equipment)
            ->with('success', 'Usage session ended successfully.');
    }

    public function utilizationReport(Request $request)
    {
        $this->authorize('viewReports', CampusEquipment::class);

        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'category' => 'nullable|in:' . implode(',', array_keys(CampusEquipment::CATEGORIES)),
        ]);

        $query = CampusEquipment::with('campus')
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when(!empty($validated['campus_id']) && !$campusFilter, fn($q) => $q->where('campus_id', $validated['campus_id']))
            ->when(!empty($validated['category']), fn($q) => $q->where('category', $validated['category']));

        $equipment = $query->get()->map(function ($item) {
            $item->utilization_rate = $item->getUtilizationRate();

            // Get this month's usage
            $monthlyUsage = EquipmentUsageLog::where('equipment_id', $item->id)
                ->thisMonth()
                ->forTraining()
                ->sum('hours_used');

            $item->hours_this_month = round($monthlyUsage, 1);

            return $item;
        })->sortByDesc('utilization_rate');

        // Summary stats
        $stats = [
            'total_equipment' => $equipment->count(),
            'avg_utilization' => round($equipment->avg('utilization_rate'), 1),
            'highly_utilized' => $equipment->where('utilization_rate', '>=', 70)->count(),
            'underutilized' => $equipment->where('utilization_rate', '<', 30)->count(),
            'total_hours' => round($equipment->sum('hours_this_month'), 1),
        ];

        $campuses = $campusFilter ? [] : Campus::where('is_active', true)->pluck('name', 'id');
        $categories = CampusEquipment::CATEGORIES;

        return view('equipment.utilization-report', compact('equipment', 'stats', 'campuses', 'categories', 'validated'));
    }
}
