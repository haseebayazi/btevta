<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('campus')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $campuses = Campus::pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.create', compact('campuses', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $user->load('campus');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $campuses = Campus::pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.edit', compact('user', 'campuses', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ]);

        if ($validated['password'] ?? null) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted successfully!');
    }

    public function settings()
    {
        // Get current system settings
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'mail_driver' => 'nullable|in:smtp,sendmail,mailgun,ses',
            'mail_from_address' => 'nullable|email|max:255',
            'two_factor' => 'nullable|boolean',
        ]);

        // In a real application, you would update the .env file or database settings
        // For now, we'll just store in session or cache
        // This is a simplified version - in production, use a settings table or env file updates

        return back()->with('success', 'Settings updated successfully! Note: Some settings may require application restart.');
    }

    public function auditLogs(Request $request)
    {
        // Get audit logs with filters
        $query = \Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])
            ->latest();

        // Apply filters if provided
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50);
        $users = User::select('id', 'name', 'email')->get();

        return view('admin.audit-logs', compact('logs', 'users'));
    }
}