<?php

if (!function_exists('activity')) {
    /**
     * Get the activity logger instance
     *
     * @param string|null $logName
     * @return \Spatie\Activitylog\ActivityLogger
     * @throws \Exception
     */
    function activity(?string $logName = null)
    {
        if (!class_exists(\Spatie\Activitylog\ActivityLogger::class)) {
            throw new \Exception(
                'Spatie ActivityLog package is not installed. ' .
                'Run: composer require spatie/laravel-activitylog'
            );
        }

        $defaultLogName = config('activitylog.default_log_name', 'default');
        $logName = $logName ?? $defaultLogName;

        return app(\Spatie\Activitylog\ActivityLogger::class)
            ->useLog($logName);
    }
}