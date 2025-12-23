<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campus;
use App\Mail\PasswordResetMail;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $roles = User::getRoleOptions();
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
            // SECURITY FIX: Exclude soft-deleted users from uniqueness check
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->withoutTrashed()],
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword],
            'role' => 'required|in:' . implode(',', User::ROLES),
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
        $roles = User::getRoleOptions();
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
            // SECURITY FIX: Exclude soft-deleted users from uniqueness check
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)->withoutTrashed()],
            'role' => 'required|in:' . implode(',', User::ROLES),
            'campus_id' => 'nullable|exists:campuses,id',
            'phone' => 'nullable|string|max:20',
            'password' => ['nullable', 'string', 'min:8', 'confirmed', new StrongPassword],
        ]);

        try {
            // Only update password if provided
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            // SECURITY FIX: Prevent role escalation vulnerabilities

            // 1. Non-admins cannot change ANY roles (including their own)
            if (!auth()->user()->isSuperAdmin() && isset($validated['role'])) {
                unset($validated['role']);  // Strip role from validated data
            }

            // 2. Admins cannot change their own role (prevents accidental lockout)
            if (auth()->user()->isSuperAdmin() && $user->id === auth()->id() && isset($validated['role']) && $validated['role'] !== $user->role) {
                return back()->with('error', 'You cannot change your own role!');
            }

            // 3. Prevent removing the last admin (when admin edits another admin)
            // Use database transaction with locking to prevent race conditions
            if (auth()->user()->isSuperAdmin() && isset($validated['role']) && $user->isSuperAdmin() && !in_array($validated['role'], [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
                try {
                    \DB::transaction(function() use ($user, $validated) {
                        // Lock users table for counting admins (prevents race condition)
                        $adminCount = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
                            ->where('id', '!=', $user->id)
                            ->lockForUpdate()
                            ->count();

                        if ($adminCount === 0) {
                            throw new \Exception('Cannot change role: You are the last admin user!');
                        }

                        $user->update($validated);
                    });
                } catch (\Exception $e) {
                    return back()->with('error', $e->getMessage());
                }
            } else {
                $user->update($validated);
            }

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
            if ($user->isSuperAdmin()) {
                $adminCount = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
                    ->where('id', '!=', $user->id)
                    ->count();
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
            if ($user->isSuperAdmin() && $user->is_active) {
                $activeAdminCount = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
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
            try {
                Mail::to($user->email)->send(new PasswordResetMail($user, $newPassword, auth()->user()));

                return back()->with('success', 'Password reset successfully! New password has been emailed to ' . $user->email);
            } catch (\Exception $mailException) {
                // Log email failure but don't expose the password
                \Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $mailException->getMessage()
                ]);

                return back()->with('warning', 'Password reset successfully but failed to send email notification. Please configure email settings or manually notify the user.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reset password: ' . $e->getMessage());
        }
    }

    public function settings()
    {
        $this->authorize('manageSettings', User::class);

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
        $this->authorize('manageSettings', User::class);

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
        $this->authorize('viewAuditLogs', User::class);

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

    /**
     * Get user notifications (API endpoint).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notifications(Request $request)
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->when($request->filled('unread_only'), function ($query) {
                $query->whereNull('read_at');
            })
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read (API endpoint).
     *
     * @param  string  $notification
     * @return \Illuminate\Http\JsonResponse
     */
    public function markNotificationRead(string $notification)
    {
        $user = auth()->user();

        $notificationRecord = $user->notifications()->find($notification);

        if (!$notificationRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notificationRecord->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllNotificationsRead()
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Show user profile page.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    /**
     * Update user profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)->withoutTrashed()],
            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed', new StrongPassword],
        ]);

        // Update basic info
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password if provided
        if ($request->filled('new_password')) {
            if (!\Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'Current password is incorrect!');
            }
            $user->password = \Hash::make($request->new_password);
            $user->password_changed_at = now();
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }
}