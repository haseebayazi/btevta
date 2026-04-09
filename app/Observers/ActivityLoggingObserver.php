<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Generic activity observer that logs create / update / delete events
 * for any model it is registered against.
 *
 * Register in AppServiceProvider::boot() for all key models, e.g.:
 *   Training::observe(ActivityLoggingObserver::class);
 *   VisaProcess::observe(ActivityLoggingObserver::class);
 */
class ActivityLoggingObserver
{
    public function created(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->withProperties(['attributes' => $this->sanitize($model->getAttributes())])
            ->log('created');
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->withProperties([
                'old' => $this->sanitize(array_intersect_key($model->getOriginal(), $changes)),
                'new' => $this->sanitize($changes),
            ])
            ->log('updated');
    }

    public function deleted(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(Auth::user())
            ->log('deleted');
    }

    /**
     * Remove sensitive fields before persisting to the activity log.
     */
    private function sanitize(array $data): array
    {
        $sensitive = ['password', 'remember_token', 'cnic', 'passport_number', 'emergency_contact'];

        foreach ($sensitive as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[redacted]';
            }
        }

        return $data;
    }
}
