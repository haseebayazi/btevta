<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * API Token Controller
 *
 * Handles Sanctum token creation, listing, and revocation for API authentication.
 * SECURITY: All token operations are logged for audit purposes.
 */
class ApiTokenController extends Controller
{
    /**
     * Create a new API token for the user.
     *
     * POST /api/v1/auth/token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        // Find user by email (excluding soft-deleted)
        $user = User::where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();

        // SECURITY: Check if user exists and is not locked
        if (!$user) {
            Log::warning('API token creation failed - user not found', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            Log::warning('API token creation failed - account locked', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);
            throw ValidationException::withMessages([
                'email' => ['Your account is locked. Please try again later.'],
            ]);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            // Increment failed login attempts
            $attempts = ($user->failed_login_attempts ?? 0) + 1;
            $updateData = ['failed_login_attempts' => $attempts];

            if ($attempts >= 5) {
                $updateData['locked_until'] = now()->addMinutes(15);
                Log::warning('Account locked due to failed API token attempts', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);
            }

            $user->update($updateData);

            Log::warning('API token creation failed - invalid password', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            Log::warning('API token creation failed - user inactive', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Reset failed login attempts on successful auth
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        // Create the token
        $token = $user->createToken($request->device_name);

        Log::info('API token created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'device_name' => $request->device_name,
            'ip' => $request->ip(),
        ]);

        activity()
            ->causedBy($user)
            ->log('API token created for device: ' . $request->device_name);

        return response()->json([
            'success' => true,
            'message' => 'Token created successfully',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ],
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * GET /api/v1/auth/user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentUser(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'campus_id' => $user->campus_id,
                'oep_id' => $user->oep_id,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

    /**
     * List all tokens for the authenticated user.
     *
     * GET /api/v1/auth/tokens
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listTokens(Request $request)
    {
        $user = $request->user();
        $tokens = $user->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Revoke a specific token.
     *
     * DELETE /api/v1/auth/tokens/{tokenId}
     *
     * @param Request $request
     * @param int $tokenId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $user = $request->user();
        $token = $user->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);
        }

        $tokenName = $token->name;
        $token->delete();

        Log::info('API token revoked', [
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'token_name' => $tokenName,
            'ip' => $request->ip(),
        ]);

        activity()
            ->causedBy($user)
            ->log('API token revoked: ' . $tokenName);

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully',
        ]);
    }

    /**
     * Revoke all tokens for the authenticated user.
     *
     * DELETE /api/v1/auth/tokens
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeAllTokens(Request $request)
    {
        $user = $request->user();
        $count = $user->tokens()->count();
        $user->tokens()->delete();

        Log::info('All API tokens revoked', [
            'user_id' => $user->id,
            'tokens_revoked' => $count,
            'ip' => $request->ip(),
        ]);

        activity()
            ->causedBy($user)
            ->log('All API tokens revoked (' . $count . ' tokens)');

        return response()->json([
            'success' => true,
            'message' => "All tokens revoked successfully ({$count} tokens)",
        ]);
    }
}
