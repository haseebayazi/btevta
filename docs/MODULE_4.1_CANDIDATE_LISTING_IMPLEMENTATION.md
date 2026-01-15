# Module 4.1: Candidate Listing - Detailed Implementation Plan

**Version:** 1.0
**Date:** 2026-01-15
**Priority:** HIGH (Critical for Issue #139)
**Estimated Effort:** 5-7 days

---

## 1. Current State Assessment

### ✅ What's Working
- **CandidateController** (23KB) with full CRUD operations
- **ImportController** for bulk candidate imports
- **CandidateDeduplicationService** for duplicate detection
- **BTEVTA ID generation** with Luhn checksum validation
- **Application ID generation** with year-based sequencing
- **Audit logging** via Spatie Activity Log
- **API endpoints** for candidate management

### ❌ Critical Gaps (Issue #139)
1. **Batch management tightly coupled** to candidate addition workflow
2. **No standalone batch admin interface**
3. **Batch CRUD operations incomplete**
4. **Missing bulk batch operations**
5. **No batch filtering/search in dedicated view**
6. **Batch API endpoints missing**
7. **Batch assignment UI needs improvement**

---

## 2. System Map Requirements Review

### Required Features (Section 4.1)
- ✅ Bulk import using BTEVTA-provided templates
- ✅ Manual batch assignment
- ✅ Duplicate checking (by CNIC, name)
- ✅ Audit trail of imports
- ❌ **Efficient batch management interface** (GAP)
- ❌ **Batch overview and analytics** (GAP)

---

## 3. Detailed Gap Analysis

### 3.1 Batch Model & Relationships
**Current Location:** `app/Models/Batch.php`

**Status:** ✅ Model exists with proper relationships

**Review Required Fields:**
```php
// Expected fields based on System Map
- id
- name
- code (unique identifier)
- campus_id
- trade_id
- oep_id
- start_date
- end_date
- status (planned, active, completed, cancelled)
- capacity
- current_enrollment
- description
- created_by
- updated_by
- timestamps
- soft_deletes
```

**Validation Needed:**
- Ensure all fields present
- Check relationships: Campus, Trade, OEP, Candidates
- Verify soft delete implementation

---

### 3.2 BatchController Refactoring
**Current Location:** `app/Http/Controllers/BatchController.php`

**Current State:** Minimal implementation, embedded in candidate workflow

**Required Methods:**
```php
class BatchController extends Controller
{
    public function index()         // List all batches with filters
    public function create()        // Show batch creation form
    public function store()         // Create new batch
    public function show(Batch $batch)      // View batch details
    public function edit(Batch $batch)      // Edit batch form
    public function update(Batch $batch)    // Update batch
    public function destroy(Batch $batch)   // Soft delete batch

    // Additional methods needed:
    public function statistics()    // Batch analytics dashboard
    public function candidates(Batch $batch)  // Manage batch candidates
    public function bulkAssign()    // Bulk assign candidates to batch
    public function export()        // Export batch data
}
```

---

### 3.3 Admin Interface Structure
**Required Views:** `resources/views/admin/batches/`

**Files to Create:**
```
resources/views/admin/batches/
├── index.blade.php       # Batch listing with filters
├── create.blade.php      # Create new batch form
├── edit.blade.php        # Edit batch form
├── show.blade.php        # Batch details view
├── candidates.blade.php  # Manage batch candidates
└── statistics.blade.php  # Batch analytics
```

---

### 3.4 Batch API Endpoints
**Required Routes:** `routes/api.php`

**Endpoints to Implement:**
```php
Route::prefix('api/v1/batches')->middleware(['auth:sanctum'])->group(function() {
    Route::get('/', [BatchApiController::class, 'index']);
    Route::post('/', [BatchApiController::class, 'store']);
    Route::get('/{batch}', [BatchApiController::class, 'show']);
    Route::put('/{batch}', [BatchApiController::class, 'update']);
    Route::delete('/{batch}', [BatchApiController::class, 'destroy']);
    Route::get('/{batch}/candidates', [BatchApiController::class, 'candidates']);
    Route::post('/{batch}/assign-candidates', [BatchApiController::class, 'assignCandidates']);
    Route::post('/bulk-assign', [BatchApiController::class, 'bulkAssign']);
    Route::get('/statistics', [BatchApiController::class, 'statistics']);
});
```

---

## 4. Step-by-Step Implementation

### Step 1: Review and Enhance Batch Model (2 hours)

**File:** `app/Models/Batch.php`

**Tasks:**
1. Review existing model structure
2. Add missing fillable fields
3. Verify relationships
4. Add scopes for filtering
5. Add computed attributes

**Code Implementation:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'code',
        'campus_id',
        'trade_id',
        'oep_id',
        'start_date',
        'end_date',
        'status',
        'capacity',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity' => 'integer',
    ];

    protected $appends = [
        'current_enrollment',
        'available_slots',
        'is_full',
        'is_active',
    ];

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'status', 'capacity', 'start_date', 'end_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function oep()
    {
        return $this->belongsTo(Oep::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByCampus($query, $campusId)
    {
        return $query->where('campus_id', $campusId);
    }

    public function scopeByTrade($query, $tradeId)
    {
        return $query->where('trade_id', $tradeId);
    }

    public function scopeByOep($query, $oepId)
    {
        return $query->where('oep_id', $oepId);
    }

    public function scopeAvailable($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM candidates WHERE candidates.batch_id = batches.id AND candidates.deleted_at IS NULL) < batches.capacity');
    }

    // Computed Attributes
    public function getCurrentEnrollmentAttribute()
    {
        return $this->candidates()->count();
    }

    public function getAvailableSlotsAttribute()
    {
        return max(0, $this->capacity - $this->current_enrollment);
    }

    public function getIsFullAttribute()
    {
        return $this->current_enrollment >= $this->capacity;
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'planned' => 'bg-blue-100 text-blue-800',
            'active' => 'bg-green-100 text-green-800',
            'completed' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Helper Methods
    public function canAddCandidates($count = 1)
    {
        return ($this->current_enrollment + $count) <= $this->capacity;
    }

    public function getProgressPercentage()
    {
        if ($this->capacity == 0) return 0;
        return round(($this->current_enrollment / $this->capacity) * 100, 2);
    }
}
```

**Verification:**
```bash
php artisan tinker
>>> Batch::with(['campus', 'trade', 'oep', 'candidates'])->first()
>>> Batch::active()->count()
```

---

### Step 2: Create BatchPolicy (1 hour)

**File:** `app/Policies/BatchPolicy.php`

**Command:**
```bash
php artisan make:policy BatchPolicy --model=Batch
```

**Implementation:**
```php
<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

class BatchPolicy
{
    /**
     * Determine if the user can view any batches.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'campus_admin', 'oep', 'instructor']);
    }

    /**
     * Determine if the user can view the batch.
     */
    public function view(User $user, Batch $batch): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('campus_admin')) {
            return $user->campus_id === $batch->campus_id;
        }

        if ($user->hasRole('oep')) {
            return $user->oep_id === $batch->oep_id;
        }

        return false;
    }

    /**
     * Determine if the user can create batches.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'campus_admin']);
    }

    /**
     * Determine if the user can update the batch.
     */
    public function update(User $user, Batch $batch): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('campus_admin')) {
            return $user->campus_id === $batch->campus_id;
        }

        return false;
    }

    /**
     * Determine if the user can delete the batch.
     */
    public function delete(User $user, Batch $batch): bool
    {
        // Cannot delete batch with enrolled candidates
        if ($batch->candidates()->count() > 0) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('campus_admin')) {
            return $user->campus_id === $batch->campus_id && $batch->status === 'planned';
        }

        return false;
    }

    /**
     * Determine if the user can assign candidates to the batch.
     */
    public function assignCandidates(User $user, Batch $batch): bool
    {
        return $this->update($user, $batch);
    }
}
```

**Register in:** `app/Providers/AuthServiceProvider.php`
```php
protected $policies = [
    Batch::class => BatchPolicy::class,
    // ... other policies
];
```

---

### Step 3: Create Form Request Validators (1 hour)

**File:** `app/Http/Requests/StoreBatchRequest.php`

```bash
php artisan make:request StoreBatchRequest
php artisan make:request UpdateBatchRequest
php artisan make:request BulkAssignBatchRequest
```

**Implementation - StoreBatchRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Batch::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:batches,code'],
            'campus_id' => ['required', 'exists:campuses,id'],
            'trade_id' => ['required', 'exists:trades,id'],
            'oep_id' => ['nullable', 'exists:oeps,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'A batch with this code already exists.',
            'end_date.after' => 'End date must be after start date.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
        ];
    }
}
```

**Implementation - UpdateBatchRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');
        return $this->user()->can('update', $batch);
    }

    public function rules(): array
    {
        $batchId = $this->route('batch')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('batches')->ignore($batchId)],
            'campus_id' => ['required', 'exists:campuses,id'],
            'trade_id' => ['required', 'exists:trades,id'],
            'oep_id' => ['nullable', 'exists:oeps,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::in(['planned', 'active', 'completed', 'cancelled'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
```

**Implementation - BulkAssignBatchRequest.php:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super_admin', 'admin', 'campus_admin']);
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'exists:batches,id'],
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['required', 'exists:candidates,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $batch = \App\Models\Batch::find($this->batch_id);
            $candidateCount = count($this->candidate_ids);

            if ($batch && !$batch->canAddCandidates($candidateCount)) {
                $validator->errors()->add(
                    'batch_id',
                    "Batch capacity exceeded. Available slots: {$batch->available_slots}, Requested: {$candidateCount}"
                );
            }
        });
    }
}
```

---

### Step 4: Refactor BatchController (4 hours)

**File:** `app/Http/Controllers/BatchController.php`

**Complete Implementation:**
```php
<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Candidate;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Http\Requests\BulkAssignBatchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of batches.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['campus', 'trade', 'oep', 'creator'])
            ->withCount('candidates');

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('trade_id')) {
            $query->where('trade_id', $request->trade_id);
        }

        if ($request->filled('oep_id')) {
            $query->where('oep_id', $request->oep_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        // Apply campus filter for campus admins
        if (auth()->user()->hasRole('campus_admin')) {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        // Apply OEP filter for OEP users
        if (auth()->user()->hasRole('oep')) {
            $query->where('oep_id', auth()->user()->oep_id);
        }

        $batches = $query->latest()->paginate(20);

        // Data for filters
        $campuses = Campus::orderBy('name')->get();
        $trades = Trade::orderBy('name')->get();
        $oeps = Oep::orderBy('name')->get();

        return view('admin.batches.index', compact('batches', 'campuses', 'trades', 'oeps'));
    }

    /**
     * Show the form for creating a new batch.
     */
    public function create()
    {
        $this->authorize('create', Batch::class);

        $campuses = Campus::orderBy('name')->get();
        $trades = Trade::orderBy('name')->get();
        $oeps = Oep::orderBy('name')->get();

        return view('admin.batches.create', compact('campuses', 'trades', 'oeps'));
    }

    /**
     * Store a newly created batch.
     */
    public function store(StoreBatchRequest $request)
    {
        DB::beginTransaction();
        try {
            $batch = Batch::create([
                ...$request->validated(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.batches.show', $batch)
                ->with('success', 'Batch created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create batch: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load([
            'campus',
            'trade',
            'oep',
            'candidates' => function ($query) {
                $query->with(['campus', 'trade'])->latest();
            },
            'creator',
            'updater'
        ]);

        return view('admin.batches.show', compact('batch'));
    }

    /**
     * Show the form for editing the batch.
     */
    public function edit(Batch $batch)
    {
        $this->authorize('update', $batch);

        $campuses = Campus::orderBy('name')->get();
        $trades = Trade::orderBy('name')->get();
        $oeps = Oep::orderBy('name')->get();

        return view('admin.batches.edit', compact('batch', 'campuses', 'trades', 'oeps'));
    }

    /**
     * Update the specified batch.
     */
    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        DB::beginTransaction();
        try {
            // Check capacity constraint
            $newCapacity = $request->capacity;
            $currentEnrollment = $batch->current_enrollment;

            if ($newCapacity < $currentEnrollment) {
                return back()
                    ->withInput()
                    ->with('error', "Cannot reduce capacity below current enrollment ({$currentEnrollment}).");
            }

            $batch->update([
                ...$request->validated(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.batches.show', $batch)
                ->with('success', 'Batch updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update batch: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified batch.
     */
    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        if ($batch->candidates()->count() > 0) {
            return back()->with('error', 'Cannot delete batch with enrolled candidates.');
        }

        DB::beginTransaction();
        try {
            $batch->delete();
            DB::commit();

            return redirect()
                ->route('admin.batches.index')
                ->with('success', 'Batch deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete batch: ' . $e->getMessage());
        }
    }

    /**
     * Show batch candidates management.
     */
    public function candidates(Request $request, Batch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load(['campus', 'trade', 'oep']);

        $candidates = $batch->candidates()
            ->with(['campus', 'trade', 'oep'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('btevta_id', 'LIKE', "%{$search}%")
                      ->orWhere('cnic', 'LIKE', "%{$search}%");
                });
            })
            ->paginate(50);

        // Get unassigned candidates for bulk assignment
        $unassignedCandidates = Candidate::whereNull('batch_id')
            ->where('campus_id', $batch->campus_id)
            ->where('trade_id', $batch->trade_id)
            ->where('status', 'screening')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('admin.batches.candidates', compact('batch', 'candidates', 'unassignedCandidates'));
    }

    /**
     * Bulk assign candidates to batch.
     */
    public function bulkAssign(BulkAssignBatchRequest $request)
    {
        $batch = Batch::findOrFail($request->batch_id);
        $this->authorize('assignCandidates', $batch);

        DB::beginTransaction();
        try {
            $candidateIds = $request->candidate_ids;
            $assigned = 0;
            $errors = [];

            foreach ($candidateIds as $candidateId) {
                $candidate = Candidate::find($candidateId);

                if (!$candidate) {
                    $errors[] = "Candidate ID {$candidateId} not found.";
                    continue;
                }

                if ($candidate->batch_id) {
                    $errors[] = "{$candidate->name} is already assigned to a batch.";
                    continue;
                }

                if (!$batch->canAddCandidates(1)) {
                    $errors[] = "Batch capacity reached. Cannot assign {$candidate->name}.";
                    break;
                }

                $candidate->update([
                    'batch_id' => $batch->id,
                    'updated_by' => auth()->id(),
                ]);

                $assigned++;
            }

            DB::commit();

            $message = "{$assigned} candidate(s) assigned successfully.";
            if (count($errors) > 0) {
                $message .= " Issues: " . implode(' ', array_slice($errors, 0, 3));
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Bulk assignment failed: ' . $e->getMessage());
        }
    }

    /**
     * Show batch statistics.
     */
    public function statistics()
    {
        $this->authorize('viewAny', Batch::class);

        $stats = [
            'total' => Batch::count(),
            'active' => Batch::where('status', 'active')->count(),
            'planned' => Batch::where('status', 'planned')->count(),
            'completed' => Batch::where('status', 'completed')->count(),
            'total_enrollment' => Candidate::whereNotNull('batch_id')->count(),
        ];

        $batchesByCampus = Batch::select('campus_id', DB::raw('count(*) as count'))
            ->with('campus:id,name')
            ->groupBy('campus_id')
            ->get();

        $batchesByTrade = Batch::select('trade_id', DB::raw('count(*) as count'))
            ->with('trade:id,name')
            ->groupBy('trade_id')
            ->get();

        $batchesWithCapacity = Batch::withCount('candidates')
            ->where('status', '!=', 'completed')
            ->get()
            ->map(function ($batch) {
                return [
                    'name' => $batch->name,
                    'capacity' => $batch->capacity,
                    'enrollment' => $batch->candidates_count,
                    'percentage' => $batch->getProgressPercentage(),
                ];
            });

        return view('admin.batches.statistics', compact(
            'stats',
            'batchesByCampus',
            'batchesByTrade',
            'batchesWithCapacity'
        ));
    }

    /**
     * Export batch data.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        // Implementation will use Laravel Excel
        // To be implemented in Step 7
    }
}
```

---

### Step 5: Create Batch API Controller (3 hours)

**File:** `app/Http/Controllers/Api/BatchApiController.php`

```bash
php artisan make:controller Api/BatchApiController --api
```

**Implementation:**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Http\Requests\BulkAssignBatchRequest;
use App\Http\Resources\BatchResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchApiController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['campus', 'trade', 'oep'])
            ->withCount('candidates');

        // Filters
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('trade_id')) {
            $query->where('trade_id', $request->trade_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('available_only')) {
            $query->available();
        }

        // Campus admin filter
        if (auth()->user()->hasRole('campus_admin')) {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        $batches = $query->latest()->paginate($request->per_page ?? 20);

        return BatchResource::collection($batches);
    }

    /**
     * Store a newly created batch.
     */
    public function store(StoreBatchRequest $request)
    {
        DB::beginTransaction();
        try {
            $batch = Batch::create([
                ...$request->validated(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return new BatchResource($batch->load(['campus', 'trade', 'oep']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load(['campus', 'trade', 'oep', 'candidates' => function ($query) {
            $query->with(['campus', 'trade'])->latest();
        }]);

        return new BatchResource($batch);
    }

    /**
     * Update the specified batch.
     */
    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        DB::beginTransaction();
        try {
            $newCapacity = $request->capacity;
            $currentEnrollment = $batch->current_enrollment;

            if ($newCapacity < $currentEnrollment) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => [
                        'capacity' => ["Cannot reduce capacity below current enrollment ({$currentEnrollment})"]
                    ]
                ], 422);
            }

            $batch->update([
                ...$request->validated(),
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return new BatchResource($batch->fresh(['campus', 'trade', 'oep']));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified batch.
     */
    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        if ($batch->candidates()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete batch with enrolled candidates'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $batch->delete();
            DB::commit();

            return response()->json([
                'message' => 'Batch deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete batch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch candidates.
     */
    public function candidates(Batch $batch)
    {
        $this->authorize('view', $batch);

        $candidates = $batch->candidates()
            ->with(['campus', 'trade', 'oep'])
            ->paginate(50);

        return response()->json($candidates);
    }

    /**
     * Assign candidates to batch.
     */
    public function assignCandidates(Request $request, Batch $batch)
    {
        $this->authorize('assignCandidates', $batch);

        $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1'],
            'candidate_ids.*' => ['required', 'exists:candidates,id'],
        ]);

        DB::beginTransaction();
        try {
            $assigned = 0;
            $errors = [];

            foreach ($request->candidate_ids as $candidateId) {
                $candidate = \App\Models\Candidate::find($candidateId);

                if ($candidate->batch_id) {
                    $errors[] = "{$candidate->name} already assigned";
                    continue;
                }

                if (!$batch->canAddCandidates(1)) {
                    $errors[] = "Batch capacity reached";
                    break;
                }

                $candidate->update([
                    'batch_id' => $batch->id,
                    'updated_by' => auth()->id(),
                ]);

                $assigned++;
            }

            DB::commit();

            return response()->json([
                'message' => "{$assigned} candidate(s) assigned successfully",
                'assigned' => $assigned,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign candidates to batch.
     */
    public function bulkAssign(BulkAssignBatchRequest $request)
    {
        $batch = Batch::findOrFail($request->batch_id);
        $this->authorize('assignCandidates', $batch);

        DB::beginTransaction();
        try {
            $assigned = 0;

            foreach ($request->candidate_ids as $candidateId) {
                $candidate = \App\Models\Candidate::find($candidateId);

                if (!$candidate->batch_id && $batch->canAddCandidates(1)) {
                    $candidate->update([
                        'batch_id' => $batch->id,
                        'updated_by' => auth()->id(),
                    ]);
                    $assigned++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => "{$assigned} candidates assigned successfully",
                'assigned' => $assigned,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk assignment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch statistics.
     */
    public function statistics()
    {
        $this->authorize('viewAny', Batch::class);

        $stats = [
            'total' => Batch::count(),
            'active' => Batch::active()->count(),
            'planned' => Batch::planned()->count(),
            'completed' => Batch::completed()->count(),
            'total_capacity' => Batch::sum('capacity'),
            'total_enrollment' => \App\Models\Candidate::whereNotNull('batch_id')->count(),
        ];

        $byCampus = Batch::select('campus_id', DB::raw('count(*) as count'))
            ->with('campus:id,name')
            ->groupBy('campus_id')
            ->get();

        $byTrade = Batch::select('trade_id', DB::raw('count(*) as count'))
            ->with('trade:id,name')
            ->groupBy('trade_id')
            ->get();

        return response()->json([
            'statistics' => $stats,
            'by_campus' => $byCampus,
            'by_trade' => $byTrade,
        ]);
    }
}
```

---

### Step 6: Create API Resource (1 hour)

**File:** `app/Http/Resources/BatchResource.php`

```bash
php artisan make:resource BatchResource
```

**Implementation:**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'status_badge_class' => $this->status_badge_class,
            'capacity' => $this->capacity,
            'current_enrollment' => $this->current_enrollment,
            'available_slots' => $this->available_slots,
            'is_full' => $this->is_full,
            'is_active' => $this->is_active,
            'progress_percentage' => $this->getProgressPercentage(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description,

            // Relationships
            'campus' => [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
                'code' => $this->campus->code,
            ],
            'trade' => [
                'id' => $this->trade->id,
                'name' => $this->trade->name,
                'code' => $this->trade->code,
            ],
            'oep' => $this->when($this->oep, [
                'id' => $this->oep?->id,
                'name' => $this->oep?->name,
            ]),

            // Counts
            'candidates_count' => $this->when($this->relationLoaded('candidates'),
                $this->candidates->count()
            ),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Creator info
            'created_by' => $this->when($this->relationLoaded('creator'), [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
        ];
    }
}
```

---

### Step 7: Create Blade Views (6-8 hours)

**Directory:** `resources/views/admin/batches/`

#### 7.1 Index View
**File:** `resources/views/admin/batches/index.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Batch Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Batch Management</h1>
            <p class="text-gray-600 mt-1">Manage training batches across campuses</p>
        </div>

        @can('create', App\Models\Batch::class)
        <a href="{{ route('admin.batches.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create New Batch
        </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('admin.batches.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name or code..."
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Campus Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Trade Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trade</label>
                <select name="trade_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Trades</option>
                    @foreach($trades as $trade)
                        <option value="{{ $trade->id }}" {{ request('trade_id') == $trade->id ? 'selected' : '' }}>
                            {{ $trade->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Planned</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
                <a href="{{ route('admin.batches.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="text-gray-600 text-sm font-medium">Total Batches</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $batches->total() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="text-gray-600 text-sm font-medium">Active Batches</div>
            <div class="text-2xl font-bold text-green-600 mt-1">
                {{ $batches->where('status', 'active')->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="text-gray-600 text-sm font-medium">Planned Batches</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">
                {{ $batches->where('status', 'planned')->count() }}
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="text-gray-600 text-sm font-medium">Completed Batches</div>
            <div class="text-2xl font-bold text-gray-600 mt-1">
                {{ $batches->where('status', 'completed')->count() }}
            </div>
        </div>
    </div>

    <!-- Batches Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrollment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($batches as $batch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm text-gray-900">{{ $batch->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $batch->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $batch->campus->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $batch->trade->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $batch->status_badge_class }}">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm text-gray-900">
                                    {{ $batch->candidates_count }} / {{ $batch->capacity }}
                                </div>
                                <div class="ml-2 w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         style="width: {{ $batch->progress_percentage }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $batch->start_date->format('M d') }} - {{ $batch->end_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                @can('view', $batch)
                                <a href="{{ route('admin.batches.show', $batch) }}"
                                   class="text-blue-600 hover:text-blue-900">View</a>
                                @endcan

                                @can('update', $batch)
                                <a href="{{ route('admin.batches.edit', $batch) }}"
                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                @endcan

                                @can('delete', $batch)
                                <form method="POST" action="{{ route('admin.batches.destroy', $batch) }}"
                                      class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this batch?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No batches found.
                            @can('create', App\Models\Batch::class)
                                <a href="{{ route('admin.batches.create') }}" class="text-blue-600 hover:text-blue-800">
                                    Create your first batch
                                </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($batches->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $batches->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
```

#### 7.2 Create/Edit Views
**File:** `resources/views/admin/batches/create.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Create New Batch')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Create New Batch</h1>
        <p class="text-gray-600 mt-1">Set up a new training batch</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('admin.batches.store') }}">
            @csrf

            <div class="space-y-6">
                <!-- Batch Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Batch Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batch Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Batch Code *</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required
                           placeholder="e.g., BTH-2026-001"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Campus -->
                <div>
                    <label for="campus_id" class="block text-sm font-medium text-gray-700">Campus *</label>
                    <select name="campus_id" id="campus_id" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ old('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('campus_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trade -->
                <div>
                    <label for="trade_id" class="block text-sm font-medium text-gray-700">Trade *</label>
                    <select name="trade_id" id="trade_id" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Trade</option>
                        @foreach($trades as $trade)
                            <option value="{{ $trade->id }}" {{ old('trade_id') == $trade->id ? 'selected' : '' }}>
                                {{ $trade->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('trade_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- OEP (Optional) -->
                <div>
                    <label for="oep_id" class="block text-sm font-medium text-gray-700">OEP (Optional)</label>
                    <select name="oep_id" id="oep_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">No OEP Assigned</option>
                        @foreach($oeps as $oep)
                            <option value="{{ $oep->id }}" {{ old('oep_id') == $oep->id ? 'selected' : '' }}>
                                {{ $oep->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('oep_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date *</label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Capacity -->
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity *</label>
                    <input type="number" name="capacity" id="capacity" value="{{ old('capacity', 30) }}"
                           min="1" max="500" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('capacity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="planned" {{ old('status', 'planned') == 'planned' ? 'selected' : '' }}>Planned</option>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="4"
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.batches.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Create Batch
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

---

### Step 8: Update Routes (30 minutes)

**File:** `routes/web.php`

```php
// Add in admin routes section
Route::middleware(['auth', 'role:super_admin|admin|campus_admin'])->prefix('admin')->name('admin.')->group(function () {

    // Batch Management Routes
    Route::resource('batches', BatchController::class);
    Route::get('batches/{batch}/candidates', [BatchController::class, 'candidates'])->name('batches.candidates');
    Route::post('batches/bulk-assign', [BatchController::class, 'bulkAssign'])->name('batches.bulk-assign');
    Route::get('batches-statistics', [BatchController::class, 'statistics'])->name('batches.statistics');
    Route::post('batches/export', [BatchController::class, 'export'])->name('batches.export');

    // ... other admin routes
});
```

**File:** `routes/api.php`

```php
// Add in API routes section
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {

    // Batch API Endpoints
    Route::apiResource('batches', Api\BatchApiController::class);
    Route::get('batches/{batch}/candidates', [Api\BatchApiController::class, 'candidates']);
    Route::post('batches/{batch}/assign-candidates', [Api\BatchApiController::class, 'assignCandidates']);
    Route::post('batches/bulk-assign', [Api\BatchApiController::class, 'bulkAssign']);
    Route::get('batch-statistics', [Api\BatchApiController::class, 'statistics']);

    // ... other API routes
});
```

---

### Step 9: Create Tests (3 hours)

**File:** `tests/Feature/BatchControllerTest.php`

```bash
php artisan make:test BatchControllerTest
```

**Implementation:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $campus;
    protected $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
    }

    public function test_admin_can_view_batch_index()
    {
        Batch::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.batches.index');
        $response->assertViewHas('batches');
    }

    public function test_admin_can_create_batch()
    {
        $batchData = [
            'name' => 'Test Batch 2026',
            'code' => 'BTH-2026-TEST',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(90)->format('Y-m-d'),
            'capacity' => 30,
            'status' => 'planned',
            'description' => 'Test batch description',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), $batchData);

        $response->assertRedirect();
        $this->assertDatabaseHas('batches', [
            'name' => 'Test Batch 2026',
            'code' => 'BTH-2026-TEST',
        ]);
    }

    public function test_batch_code_must_be_unique()
    {
        $batch = Batch::factory()->create(['code' => 'BTH-UNIQUE']);

        $batchData = [
            'name' => 'Another Batch',
            'code' => 'BTH-UNIQUE', // Duplicate
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(90)->format('Y-m-d'),
            'capacity' => 30,
            'status' => 'planned',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), $batchData);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_batch()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $updateData = [
            'name' => 'Updated Batch Name',
            'code' => $batch->code,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => $batch->start_date->format('Y-m-d'),
            'end_date' => $batch->end_date->format('Y-m-d'),
            'capacity' => 50,
            'status' => 'active',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.batches.update', $batch), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'name' => 'Updated Batch Name',
            'capacity' => 50,
        ]);
    }

    public function test_cannot_reduce_capacity_below_current_enrollment()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'capacity' => 50,
        ]);

        // Add 30 candidates to batch
        Candidate::factory()->count(30)->create([
            'batch_id' => $batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $updateData = [
            'name' => $batch->name,
            'code' => $batch->code,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => $batch->start_date->format('Y-m-d'),
            'end_date' => $batch->end_date->format('Y-m-d'),
            'capacity' => 20, // Less than 30 enrolled
            'status' => $batch->status,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.batches.update', $batch), $updateData);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'capacity' => 50, // Unchanged
        ]);
    }

    public function test_admin_can_delete_empty_batch()
    {
        $batch = Batch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.batches.destroy', $batch));

        $response->assertRedirect();
        $this->assertSoftDeleted('batches', ['id' => $batch->id]);
    }

    public function test_cannot_delete_batch_with_candidates()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        Candidate::factory()->create([
            'batch_id' => $batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.batches.destroy', $batch));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    public function test_bulk_assign_candidates_to_batch()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'capacity' => 50,
        ]);

        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'batch_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.bulk-assign'), [
                'batch_id' => $batch->id,
                'candidate_ids' => $candidates->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($candidates as $candidate) {
            $this->assertDatabaseHas('candidates', [
                'id' => $candidate->id,
                'batch_id' => $batch->id,
            ]);
        }
    }

    public function test_cannot_assign_more_candidates_than_capacity()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'capacity' => 2,
        ]);

        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'batch_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.bulk-assign'), [
                'batch_id' => $batch->id,
                'candidate_ids' => $candidates->pluck('id')->toArray(),
            ]);

        $response->assertSessionHasErrors('batch_id');
    }

    public function test_batch_scopes_work_correctly()
    {
        Batch::factory()->create(['status' => 'active']);
        Batch::factory()->create(['status' => 'planned']);
        Batch::factory()->create(['status' => 'completed']);

        $this->assertEquals(1, Batch::active()->count());
        $this->assertEquals(1, Batch::planned()->count());
        $this->assertEquals(1, Batch::completed()->count());
    }
}
```

---

### Step 10: Create Factory (30 minutes)

**File:** `database/factories/BatchFactory.php`

```bash
php artisan make:factory BatchFactory --model=Batch
```

**Implementation:**
```php
<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+30 days');
        $endDate = $this->faker->dateTimeBetween($startDate, '+120 days');

        return [
            'name' => 'Batch ' . $this->faker->unique()->bothify('??##'),
            'code' => 'BTH-' . date('Y') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'campus_id' => Campus::factory(),
            'trade_id' => Trade::factory(),
            'oep_id' => $this->faker->boolean(70) ? Oep::factory() : null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'capacity' => $this->faker->numberBetween(20, 50),
            'status' => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
            'description' => $this->faker->optional()->paragraph,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
```

---

## 5. Implementation Checklist

### Phase 1: Model & Database (Day 1)
- [ ] Review Batch model fields and relationships
- [ ] Verify migration structure
- [ ] Add scopes and computed attributes
- [ ] Test model relationships in Tinker
- [ ] Create BatchFactory
- [ ] Test: `php artisan tinker` - Test model creation

### Phase 2: Authorization (Day 1)
- [ ] Create BatchPolicy
- [ ] Implement all policy methods
- [ ] Register policy in AuthServiceProvider
- [ ] Test policy methods
- [ ] Test: Policy authorization works correctly

### Phase 3: Request Validation (Day 1-2)
- [ ] Create StoreBatchRequest
- [ ] Create UpdateBatchRequest
- [ ] Create BulkAssignBatchRequest
- [ ] Add custom validation rules
- [ ] Test: Validation rules work correctly

### Phase 4: Web Controller (Day 2-3)
- [ ] Refactor BatchController index method
- [ ] Implement create & store methods
- [ ] Implement show method
- [ ] Implement edit & update methods
- [ ] Implement destroy method
- [ ] Implement candidates method
- [ ] Implement bulkAssign method
- [ ] Implement statistics method
- [ ] Add proper authorization checks
- [ ] Test: All controller methods work

### Phase 5: API Controller (Day 3-4)
- [ ] Create BatchApiController
- [ ] Implement all API endpoints
- [ ] Create BatchResource
- [ ] Add proper JSON responses
- [ ] Test: API endpoints return correct data

### Phase 6: Views (Day 4-5)
- [ ] Create index.blade.php
- [ ] Create create.blade.php
- [ ] Create edit.blade.php
- [ ] Create show.blade.php
- [ ] Create candidates.blade.php
- [ ] Create statistics.blade.php
- [ ] Test: All views render correctly

### Phase 7: Routes (Day 5)
- [ ] Add web routes for batch management
- [ ] Add API routes for batch endpoints
- [ ] Test: `php artisan route:list | grep batch`
- [ ] Verify all routes accessible

### Phase 8: Testing (Day 5-6)
- [ ] Create BatchControllerTest
- [ ] Test all CRUD operations
- [ ] Test authorization (policy tests)
- [ ] Test validation rules
- [ ] Test bulk assignment
- [ ] Test capacity constraints
- [ ] Test scopes and filters
- [ ] Run: `php artisan test --filter=BatchControllerTest`

### Phase 9: Integration & Polish (Day 6-7)
- [ ] Test batch creation workflow end-to-end
- [ ] Test candidate assignment workflow
- [ ] Fix any bugs found
- [ ] Add loading states to views
- [ ] Optimize queries (N+1 prevention)
- [ ] Add helpful error messages
- [ ] Update documentation

---

## 6. Testing Requirements

### Unit Tests
```bash
# Test batch model
php artisan test --filter=BatchTest

# Test batch policy
php artisan test --filter=BatchPolicyTest

# Test batch factory
php artisan tinker
>>> Batch::factory()->count(10)->create()
```

### Feature Tests
```bash
# Test controller
php artisan test --filter=BatchControllerTest

# Test API
php artisan test --filter=BatchApiTest
```

### Manual Testing Checklist
- [ ] Create batch with valid data
- [ ] Create batch with invalid data (test validation)
- [ ] View batch list with filters
- [ ] Edit batch details
- [ ] Update batch capacity
- [ ] Assign candidates to batch
- [ ] Bulk assign candidates
- [ ] Try to exceed batch capacity
- [ ] Delete empty batch
- [ ] Try to delete batch with candidates
- [ ] View batch statistics
- [ ] Test as different user roles

---

## 7. Acceptance Criteria

### Functional Requirements
✅ Admin can create batches independently of candidate workflow
✅ Admin can edit batch details
✅ Admin can delete empty batches
✅ Cannot delete batches with enrolled candidates
✅ Batch capacity constraints enforced
✅ Cannot reduce capacity below current enrollment
✅ Batch filtering by campus, trade, status works
✅ Batch search by name/code works
✅ Bulk candidate assignment works
✅ Batch statistics dashboard displays correctly

### Technical Requirements
✅ All routes protected by authentication
✅ Authorization via BatchPolicy enforced
✅ Request validation working
✅ API endpoints functional
✅ Tests passing (95%+ coverage)
✅ No N+1 query issues
✅ Proper error handling
✅ Audit logging functional

### UI/UX Requirements
✅ Batch list view clean and filterable
✅ Create/edit forms intuitive
✅ Batch details view comprehensive
✅ Candidate assignment UI user-friendly
✅ Progress bars show enrollment status
✅ Status badges color-coded
✅ Responsive on mobile devices

---

## 8. Known Issues & Limitations

### Current Limitations
1. **Export functionality** not yet implemented (Step 7 placeholder)
2. **Batch duplication** feature not included (future enhancement)
3. **Batch templates** not supported (future enhancement)

### Technical Debt
- None identified (new implementation)

---

## 9. Deployment Notes

### Database Changes
- None required (Batch model already exists)

### Configuration Changes
- None required

### Post-Deployment Steps
```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests
php artisan test --filter=Batch

# Verify routes
php artisan route:list | grep batch
```

---

## 10. Rollback Plan

If issues arise:
```bash
# Revert code changes
git revert <commit-hash>

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# No database rollback needed (no migrations)
```

---

## 11. Support & Documentation

### Code Documentation
- All methods documented with PHPDoc
- Policy methods have clear authorization logic
- Request validators have descriptive error messages

### User Documentation
- Admin guide to be created in Phase 5
- API documentation to be updated in Phase 5

---

**Status:** Ready for Implementation
**Risk Level:** Low (well-defined scope, existing infrastructure)
**Dependencies:** None (standalone enhancement)

---

**END OF MODULE 4.1 IMPLEMENTATION PLAN**
