<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Enums\TrainingType;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $query = Course::withCount(['candidateCourses']);

        // Filter by training type
        if ($request->filled('training_type')) {
            $query->where('training_type', $request->training_type);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->latest()->paginate(20);

        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $this->authorize('create', Course::class);

        $trainingTypes = TrainingType::toArray();

        return view('admin.courses.create', compact('trainingTypes'));
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $course = Course::create($validated);

            // Log activity
            activity()
                ->performedOn($course)
                ->causedBy(auth()->user())
                ->log('Course created');

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create course: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $this->authorize('view', $course);

        $course->load(['candidateCourses' => function ($query) {
            $query->with('candidate')->latest()->limit(20);
        }]);

        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the course.
     */
    public function edit(Course $course)
    {
        $this->authorize('update', $course);

        $trainingTypes = TrainingType::toArray();

        return view('admin.courses.edit', compact('course', 'trainingTypes'));
    }

    /**
     * Update the specified course.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', $course->is_active);
            $course->update($validated);

            // Log activity
            activity()
                ->performedOn($course)
                ->causedBy(auth()->user())
                ->log('Course updated');

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update course: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);

        try {
            // Check for associated course assignments
            $assignmentsCount = $course->candidateCourses()->count();
            if ($assignmentsCount > 0) {
                return back()->with('error',
                    "Cannot delete course: {$assignmentsCount} assignment(s) exist. " .
                    "Please remove assignments first."
                );
            }

            // Log activity before deletion
            activity()
                ->performedOn($course)
                ->causedBy(auth()->user())
                ->log('Course deleted');

            $course->delete();

            return redirect()->route('admin.courses.index')
                ->with('success', 'Course deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete course: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of a course.
     */
    public function toggleStatus(Course $course)
    {
        $this->authorize('update', $course);

        try {
            $course->update(['is_active' => !$course->is_active]);

            $status = $course->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($course)
                ->causedBy(auth()->user())
                ->log("Course {$status}");

            return back()->with('success', "Course {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update course status: ' . $e->getMessage());
        }
    }
}
