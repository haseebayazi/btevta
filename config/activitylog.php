<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Log Settings
    |--------------------------------------------------------------------------
    |
    | This file is used for storing all the settings related to the activity
    | log. The activity log is used to track changes made to the models.
    |
    */

    'default' => env('ACTIVITY_LOGGER_DEFAULT', 'default'),

    'default_log_name' => env('ACTIVITY_LOGGER_LOG_NAME', 'default'),

    'log_options_class' => null,

    'table_name' => 'activity_log',

    'database_connection' => null,

];