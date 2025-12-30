<?php

namespace App\Observers;

use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Log;

/**
 * UserPasswordObserver
 *
 * Observes User model changes to track password history.
 * This enables password reuse prevention for government compliance.
 *
 * The observer automatically:
 * - Saves old password to history when password changes
 * - Prunes old history entries beyond configured limit
 * - Logs password change events for audit trail
 */
class UserPasswordObserver
{
    /**
     * The original password hash before update.
     */
    private static array $originalPasswords = [];

    /**
     * Handle the User "updating" event.
     *
     * Store the original password before it's changed.
     */
    public function updating(User $user): void
    {
        // Check if password is being changed
        if ($user->isDirty('password')) {
            // Store the original password hash
            self::$originalPasswords[$user->id] = $user->getOriginal('password');
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * Save the old password to history after update completes.
     */
    public function updated(User $user): void
    {
        // Check if password was changed
        if (isset(self::$originalPasswords[$user->id])) {
            $originalPassword = self::$originalPasswords[$user->id];

            // Only save to history if there was an original password
            if ($originalPassword) {
                // Add to password history
                PasswordHistory::addToHistory($user->id, $originalPassword);

                // Log the password change for audit
                Log::info('Password changed for user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'history_count' => PasswordHistory::countForUser($user->id),
                ]);
            }

            // Clean up stored password
            unset(self::$originalPasswords[$user->id]);
        }
    }

    /**
     * Handle the User "created" event.
     *
     * Initialize password history for new users.
     */
    public function created(User $user): void
    {
        // For new users, add their initial password to history
        // This prevents them from immediately "changing back" to the same password
        if ($user->password) {
            PasswordHistory::addToHistory($user->id, $user->password);

            Log::info('Initial password history created for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * Clean up password history when user is deleted.
     */
    public function deleted(User $user): void
    {
        // If soft deletes are used, we keep the history
        // It will be cleaned up when the user is force deleted

        if ($user->isForceDeleting()) {
            $deletedCount = PasswordHistory::clearHistory($user->id);

            Log::info('Password history cleared for deleted user', [
                'user_id' => $user->id,
                'deleted_entries' => $deletedCount,
            ]);
        }
    }

    /**
     * Handle the User "forceDeleted" event.
     *
     * Clean up password history when user is permanently deleted.
     */
    public function forceDeleted(User $user): void
    {
        $deletedCount = PasswordHistory::clearHistory($user->id);

        Log::info('Password history cleared for force-deleted user', [
            'user_id' => $user->id,
            'deleted_entries' => $deletedCount,
        ]);
    }
}
