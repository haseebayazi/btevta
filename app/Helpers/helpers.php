<?php

/**
 * AUDIT FIX (P3): Status display helper functions
 * Centralizes status label and color logic for consistent display across views.
 */

if (!function_exists('status_label')) {
    /**
     * Get the display label for a status value.
     *
     * @param string $type The status type (candidate, document, screening, etc.)
     * @param string $status The status value
     * @return string The formatted label
     */
    function status_label(string $type, string $status): string
    {
        $labels = config("statuses.{$type}_labels", []);
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('status_color')) {
    /**
     * Get the CSS color class for a status value.
     *
     * @param string $type The status type (candidate, document, screening, etc.)
     * @param string $status The status value
     * @return string The Tailwind CSS color class
     */
    function status_color(string $type, string $status): string
    {
        // Check type-specific colors first (e.g., priority_colors)
        $typeColors = config("statuses.{$type}_colors", []);
        if (isset($typeColors[$status])) {
            return $typeColors[$status];
        }

        // Fall back to general colors
        $colors = config('statuses.colors', []);
        return $colors[$status] ?? 'gray';
    }
}

if (!function_exists('status_badge')) {
    /**
     * Generate a complete status badge HTML.
     *
     * @param string $type The status type (candidate, document, screening, etc.)
     * @param string $status The status value
     * @return string The HTML for the status badge
     */
    function status_badge(string $type, string $status): string
    {
        $label = status_label($type, $status);
        $color = status_color($type, $status);

        return sprintf(
            '<span class="inline-block px-2 py-1 rounded-full text-xs font-semibold bg-%s-100 text-%s-800">%s</span>',
            $color,
            $color,
            e($label)
        );
    }
}

if (!function_exists('safe_relationship')) {
    /**
     * Safely access a relationship property with fallback.
     *
     * AUDIT FIX (P3): Consistent error handling for missing relationships.
     *
     * @param mixed $model The model instance
     * @param string $relationship The relationship name
     * @param string $property The property to access on the relationship
     * @param mixed $default The default value if relationship is null
     * @return mixed
     */
    function safe_relationship($model, string $relationship, string $property, $default = 'N/A')
    {
        if (!$model || !$model->$relationship) {
            return $default;
        }
        return $model->$relationship->$property ?? $default;
    }
}

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