<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WASL System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Workforce Abroad Skills & Linkages (WASL)
    | system. These settings control various aspects of the candidate workflow,
    | batch management, and assessment criteria.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Batch Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automatic batch creation and management.
    |
    */
    'batch_size' => env('WASL_BATCH_SIZE', 25),
    'allowed_batch_sizes' => [20, 25, 30],

    /*
    |--------------------------------------------------------------------------
    | Assessment Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for training assessments and passing criteria.
    |
    */
    'assessment' => [
        'passing_percentage' => env('WASL_PASSING_PERCENTAGE', 60),
        'interim_required' => true,
        'final_required' => true,
        'max_score' => 100,
        'evidence_required' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Training Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for training programs and attendance requirements.
    |
    */
    'training' => [
        'minimum_attendance_percentage' => env('WASL_MIN_ATTENDANCE', 80),
        'technical_training_required' => true,
        'soft_skills_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for document uploads and validation.
    |
    */
    'documents' => [
        'max_file_size' => 10240, // 10MB in KB
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ],
        'photo_max_size' => 5120, // 5MB in KB
        'video_max_size' => 51200, // 50MB in KB
        'evidence_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Screening Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for candidate screening workflow.
    |
    */
    'screening' => [
        'consent_required' => true,
        'placement_interest_required' => true,
        'evidence_upload_enabled' => true,
        'reviewer_required' => true,
        'auto_status_update' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for candidate registration and allocation.
    |
    */
    'registration' => [
        'screening_gate_enabled' => true,
        'auto_batch_creation' => true,
        'auto_oep_allocation' => true,
        'allocation_required_fields' => [
            'campus_id',
            'program_id',
            'trade_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for video upload and processing.
    |
    */
    'video' => [
        'thumbnail_enabled' => true,
        'thumbnail_time' => 5, // seconds
        'compression_enabled' => true,
        'compression_threshold' => 52428800, // 50MB in bytes
        'compression_resolution' => '1280x720',
        'compression_bitrate' => 1000, // kbps
        'queue_enabled' => true,
        'max_retries' => 3,
        'timeout' => 600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Visa Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for visa processing workflow.
    |
    */
    'visa' => [
        'stages_required' => true,
        'appointment_tracking' => true,
        'result_tracking' => true,
        'evidence_required_per_stage' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Departure Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for departure processing.
    |
    */
    'departure' => [
        'ptn_status_required' => true,
        'protector_status_required' => true,
        'ticket_details_required' => true,
        'briefing_upload_enabled' => true,
        'briefing_video_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-Departure Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for post-departure tracking.
    |
    */
    'post_departure' => [
        'employment_tracking_enabled' => true,
        'max_company_switches' => 2,
        'residency_tracking_required' => true,
        'success_story_encouraged' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Success Stories Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for success story collection.
    |
    */
    'success_stories' => [
        'featured_enabled' => true,
        'evidence_types' => [
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
            'video' => ['video/mp4', 'video/mpeg', 'video/quicktime'],
            'written' => ['text/plain', 'application/pdf'],
            'screenshot' => ['image/jpeg', 'image/png'],
            'document' => ['application/pdf'],
        ],
        'max_featured_count' => 10,
        'auto_approval' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for system notifications.
    |
    */
    'notifications' => [
        'status_change_enabled' => true,
        'assessment_completion' => true,
        'batch_creation' => true,
        'screening_result' => true,
        'departure_reminder' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for dashboard widgets and metrics.
    |
    */
    'dashboard' => [
        'recent_days' => 7,
        'metrics_cache_ttl' => 3600, // 1 hour in seconds
        'charts_enabled' => true,
        'export_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Employer Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for employer information module.
    |
    */
    'employer' => [
        'permission_number_required' => false,
        'evidence_upload_enabled' => true,
        'employment_package_required' => true,
        'multiple_employers_per_candidate' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Implementing Partner Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for implementing partner management.
    |
    */
    'implementing_partner' => [
        'contact_person_required' => true,
        'agreement_tracking' => true,
        'active_status_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Program Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for program management.
    |
    */
    'program' => [
        'duration_min_weeks' => 1,
        'duration_max_weeks' => 104, // 2 years
        'active_status_required' => true,
        'country_linking_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Course Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for course management.
    |
    */
    'course' => [
        'duration_min_days' => 1,
        'duration_max_days' => 365,
        'training_types' => ['technical', 'soft_skills', 'both'],
        'active_status_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for complaint workflow.
    |
    */
    'complaint' => [
        'workflow_fields_required' => true,
        'issue_description_required' => true,
        'steps_taken_required' => false,
        'suggestions_required' => false,
        'conclusion_required' => false,
    ],
];
