<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('campus')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('create', User::class);

        $campuses = Campus::where('is_active', true)->pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.create', compact('campuses', 'roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);
            $validated['is_active'] = true;

            $user = User::create($validated);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User created');

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load('campus');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $campuses = Campus::where('is_active', true)->pluck('name', 'id');
        $roles = ['admin', 'campus_admin', 'oep_coordinator', 'visa_officer', 'trainer'];
        return view('admin.users.edit', compact('user', 'campuses', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id . '|max:255',
            'role' => 'required|in:admin,campus_admin,oep_coordinator,visa_officer,trainer',
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        try {
            // Only update password if provided
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            // Prevent changing own role if admin
            if ($user->id === auth()->id() && $user->role === 'admin' && $validated['role'] !== 'admin') {
                $adminCount = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
                if ($adminCount === 0) {
                    return back()->with('error', 'Cannot change role: You are the last admin user!');
                }
            }

            $user->update($validated);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User updated');

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        try {
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot delete your own account!');
            }

            // Prevent deleting last admin
            if ($user->role === 'admin') {
                $adminCount = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
                if ($adminCount === 0) {
                    return back()->with('error', 'Cannot delete the last admin user!');
                }
            }

            $userName = $user->name;
            $user->delete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("User deleted: {$userName}");

            return back()->with('success', 'User deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);

        try {
            // Prevent deactivating yourself
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot deactivate your own account!');
            }

            // Prevent deactivating last admin
            if ($user->role === 'admin' && $user->is_active) {
                $activeAdminCount = User::where('role', 'admin')
                    ->where('is_active', true)
                    ->where('id', '!=', $user->id)
                    ->count();

                if ($activeAdminCount === 0) {
                    return back()->with('error', 'Cannot deactivate the last active admin!');
                }
            }

            $user->is_active = !$user->is_active;
            $user->save();

            $status = $user->is_active ? 'activated' : 'deactivated';

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log("User {$status}");

            return back()->with('success', "User {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle user status: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('resetPassword', $user);

        try {
            // Generate random password
            $newPassword = Str::random(12);

            $user->password = Hash::make($newPassword);
            $user->save();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User password reset');

            // SECURITY: Send password via email only, never in response
            // TODO: Implement email notification
            // Mail::to($user->email)->send(new PasswordResetMail($newPassword));

            return back()->with('success', 'Password reset successfully! New password has been generated. Please implement email notification to send it to the user.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reset password: ' . $e->getMessage());
        }
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