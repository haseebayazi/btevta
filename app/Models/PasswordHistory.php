<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * PasswordHistory Model
 *
 * Stores historical password hashes to prevent password reuse.
 * This is a security requirement for government systems.
 *
 * @property int $id
 * @property int $user_id
 * @property string $password
 * @property \Carbon\Carbon $created_at
 */
class PasswordHistory extends Model
{
    /**
     * Disable default timestamps (we only use created_at)
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Boot method to auto-set created_at
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Get the user that owns this password history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================================
    // STATIC METHODS
    // ============================================================

    /**
     * Add a password to user's history.
     *
     * @param int $userId
     * @param string $hashedPassword The already-hashed password
     * @return PasswordHistory
     */
    public static function addToHistory(int $userId, string $hashedPassword): PasswordHistory
    {
        $historyCount = config('password.history_count', 5);

        // Add the new password
        $history = static::create([
            'user_id' => $userId,
            'password' => $hashedPassword,
        ]);

        // Clean up old passwords beyond the history limit
        if ($historyCount > 0) {
            static::pruneHistory($userId, $historyCount);
        }

        return $history;
    }

    /**
     * Check if a password was recently used by the user.
     *
     * @param int $userId
     * @param string $plainPassword The plain text password to check
     * @return bool True if password was recently used
     */
    public static function wasRecentlyUsed(int $userId, string $plainPassword): bool
    {
        $historyCount = config('password.history_count', 5);

        if ($historyCount <= 0) {
            return false;
        }

        $recentPasswords = static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->get();

        foreach ($recentPasswords as $history) {
            if (Hash::check($plainPassword, $history->password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the number of passwords in user's history.
     *
     * @param int $userId
     * @return int
     */
    public static function countForUser(int $userId): int
    {
        return static::where('user_id', $userId)->count();
    }

    /**
     * Prune old password history entries beyond the limit.
     *
     * @param int $userId
     * @param int $keepCount Number of entries to keep
     * @return int Number of entries deleted
     */
    public static function pruneHistory(int $userId, int $keepCount): int
    {
        // Get IDs of passwords to keep (most recent)
        $keepIds = static::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($keepCount)
            ->pluck('id');

        // Delete the rest
        return static::where('user_id', $userId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * Clear all password history for a user (use with caution).
     *
     * @param int $userId
     * @return int Number of entries deleted
     */
    public static function clearHistory(int $userId): int
    {
        return static::where('user_id', $userId)->delete();
    }
}
