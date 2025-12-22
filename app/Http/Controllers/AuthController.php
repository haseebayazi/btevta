<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Maximum failed login attempts before lockout
     */
    protected const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes
     */
    protected const LOCKOUT_MINUTES = 15;

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->filled('remember');

        // SECURITY FIX: First check if user exists and is not soft-deleted
        $user = User::where('email', $credentials['email'])
            ->whereNull('deleted_at')
            ->first();

        // SECURITY FIX: Check for account lockout
        if ($user && $this->isLockedOut($user)) {
            $remainingMinutes = Carbon::now()->diffInMinutes($user->locked_until, false);
            Log::warning('Locked account login attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'locked_until' => $user->locked_until,
            ]);
            throw ValidationException::withMessages([
                'email' => ["Your account is locked. Please try again in {$remainingMinutes} minutes."],
            ]);
        }

        // SECURITY: Let Auth::attempt() handle password verification
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // SECURITY FIX: Double-check soft-delete status after authentication
            if ($user->trashed()) {
                Auth::logout();
                Log::warning('Soft-deleted user attempted login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials do not match our records.'],
                ]);
            }

            // Check if user is active after successful authentication
            if (!$user->is_active) {
                Auth::logout();
                Log::warning('Inactive user attempted login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated. Please contact administrator.'],
                ]);
            }

            // SECURITY: Reset failed login attempts on successful login
            $this->resetFailedLoginAttempts($user);

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            $request->session()->regenerate();
            activity()->causedBy($user)->log('User logged in');
            return redirect()->intended('dashboard');
        }

        // SECURITY FIX: Track failed login attempts
        if ($user) {
            $this->incrementFailedLoginAttempts($user, $request);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Check if user account is locked out
     */
    protected function isLockedOut(User $user): bool
    {
        if (!$user->locked_until) {
            return false;
        }

        if (Carbon::now()->greaterThan($user->locked_until)) {
            // Lockout has expired, reset the counter
            $this->resetFailedLoginAttempts($user);
            return false;
        }

        return true;
    }

    /**
     * Increment failed login attempts and apply lockout if threshold exceeded
     */
    protected function incrementFailedLoginAttempts(User $user, Request $request): void
    {
        $attempts = ($user->failed_login_attempts ?? 0) + 1;
        $updateData = ['failed_login_attempts' => $attempts];

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $updateData['locked_until'] = Carbon::now()->addMinutes(self::LOCKOUT_MINUTES);

            Log::warning('Account locked due to too many failed attempts', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => $attempts,
                'ip' => $request->ip(),
                'locked_until' => $updateData['locked_until'],
            ]);
        }

        $user->update($updateData);
    }

    /**
     * Reset failed login attempts on successful login
     */
    protected function resetFailedLoginAttempts(User $user): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    public function logout(Request $request)
    {
        // Log the logout activity
        activity()
            ->causedBy(Auth::user())
            ->log('User logged out');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Password reset link sent to your email!')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                activity()
                    ->causedBy($user)
                    ->log('Password reset');
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password reset successfully!')
            : back()->withErrors(['email' => [__($status)]]);
    }
}