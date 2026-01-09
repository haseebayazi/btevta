<?php

/**
 * Centralized Status Configuration
 *
 * AUDIT FIX: Created to centralize all status values that were previously
 * hardcoded across controllers, views, and services.
 *
 * Usage:
 * - config('statuses.candidate.registered')
 * - config('statuses.document.verified')
 * - config('statuses.colors.success')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Candidate Statuses
    |--------------------------------------------------------------------------
    |
    | All possible statuses for candidates in the system workflow.
    | Use App\Enums\CandidateStatus enum for type-safe operations.
    |
    | AUDIT FIX: Synced with CandidateStatus enum - removed invalid statuses:
    | - screening_passed (intermediate state handled by CandidateScreening model)
    | - pending_registration (not in workflow - goes directly to 'registered')
    | - training_completed (use training_status='completed' instead)
    |
    */
    'candidate' => [
        'new' => 'new',
        'screening' => 'screening',
        'registered' => 'registered',
        'training' => 'training',
        'visa_process' => 'visa_process',
        'ready' => 'ready',
        'departed' => 'departed',
        'rejected' => 'rejected',
        'dropped' => 'dropped',
        'returned' => 'returned',
    ],

    'candidate_labels' => [
        'new' => 'New',
        'screening' => 'Screening',
        'registered' => 'Registered',
        'training' => 'In Training',
        'visa_process' => 'Visa Processing',
        'ready' => 'Ready to Depart',
        'departed' => 'Departed',
        'rejected' => 'Rejected',
        'dropped' => 'Dropped',
        'returned' => 'Returned',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Statuses
    |--------------------------------------------------------------------------
    */
    'document' => [
        'pending' => 'pending',
        'verified' => 'verified',
        'rejected' => 'rejected',
        'expired' => 'expired',
    ],

    'document_labels' => [
        'pending' => 'Pending Verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],

    /*
    |--------------------------------------------------------------------------
    | Training Statuses
    |--------------------------------------------------------------------------
    */
    'training' => [
        'not_started' => 'not_started',
        'in_progress' => 'in_progress',
        'completed' => 'completed',
        'failed' => 'failed',
        'dropped' => 'dropped',
    ],

    'training_labels' => [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'dropped' => 'Dropped Out',
    ],

    /*
    |--------------------------------------------------------------------------
    | Screening Statuses
    |--------------------------------------------------------------------------
    */
    'screening' => [
        'pending' => 'pending',
        'passed' => 'passed',
        'failed' => 'failed',
        'deferred' => 'deferred',
    ],

    'screening_labels' => [
        'pending' => 'Pending',
        'passed' => 'Passed',
        'failed' => 'Failed',
        'deferred' => 'Deferred',
    ],

    /*
    |--------------------------------------------------------------------------
    | Visa Statuses
    |--------------------------------------------------------------------------
    */
    'visa' => [
        'not_started' => 'not_started',
        'documents_submitted' => 'documents_submitted',
        'under_review' => 'under_review',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'issued' => 'issued',
    ],

    'visa_labels' => [
        'not_started' => 'Not Started',
        'documents_submitted' => 'Documents Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'issued' => 'Visa Issued',
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint Statuses
    |--------------------------------------------------------------------------
    | Use App\Enums\ComplaintStatus for type-safe operations.
    */
    'complaint' => [
        'open' => 'open',
        'assigned' => 'assigned',
        'in_progress' => 'in_progress',
        'resolved' => 'resolved',
        'closed' => 'closed',
        'reopened' => 'reopened',
    ],

    'complaint_labels' => [
        'open' => 'Open',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'reopened' => 'Reopened',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Colors (Bootstrap/Tailwind)
    |--------------------------------------------------------------------------
    | Centralized color mappings for status badges.
    */
    'colors' => [
        // General
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'primary' => 'primary',
        'secondary' => 'secondary',
        'dark' => 'dark',
        'light' => 'light',

        // Status-specific colors
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
        'approved' => 'success',
        'in_progress' => 'info',
        'completed' => 'success',
        'failed' => 'danger',
        'new' => 'secondary',
        'open' => 'warning',
        'closed' => 'dark',
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    | AUDIT FIX: Synced with ComplaintPriority enum
    | Removed 'critical' - use 'urgent' for highest priority
    */
    'priority' => [
        'low' => 'low',
        'normal' => 'normal',
        'high' => 'high',
        'urgent' => 'urgent',
    ],

    'priority_labels' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    'priority_colors' => [
        'low' => 'secondary',
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Statuses
    |--------------------------------------------------------------------------
    | AUDIT FIX: Added to centralize batch status values
    | Synced with App\Models\Batch constants
    */
    'batch' => [
        'planned' => 'planned',
        'active' => 'active',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ],

    'batch_labels' => [
        'planned' => 'Planned',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'batch_colors' => [
        'planned' => 'warning',
        'active' => 'success',
        'completed' => 'secondary',
        'cancelled' => 'danger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Instructor Statuses
    |--------------------------------------------------------------------------
    | AUDIT FIX: Added to centralize instructor status values
    */
    'instructor' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'on_leave' => 'On Leave',
        'terminated' => 'Terminated',
    ],

    'instructor_colors' => [
        'active' => 'success',
        'inactive' => 'secondary',
        'on_leave' => 'warning',
        'terminated' => 'danger',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remittance Statuses
    |--------------------------------------------------------------------------
    | AUDIT FIX: Added to centralize remittance status values
    */
    'remittance' => [
        'pending' => 'pending',
        'verified' => 'verified',
        'flagged' => 'flagged',
        'completed' => 'completed',
    ],

    'remittance_labels' => [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'flagged' => 'Flagged',
        'completed' => 'Completed',
    ],

    'remittance_colors' => [
        'pending' => 'warning',
        'verified' => 'success',
        'flagged' => 'danger',
        'completed' => 'info',
    ],

];
