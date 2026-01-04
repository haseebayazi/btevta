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
    */
    'candidate' => [
        'new' => 'new',
        'screening' => 'screening',
        'screening_passed' => 'screening_passed',
        'pending_registration' => 'pending_registration',
        'registered' => 'registered',
        'training' => 'training',
        'training_completed' => 'training_completed',
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
        'screening_passed' => 'Screening Passed',
        'pending_registration' => 'Pending Registration',
        'registered' => 'Registered',
        'training' => 'In Training',
        'training_completed' => 'Training Completed',
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
    */
    'priority' => [
        'low' => 'low',
        'normal' => 'normal',
        'high' => 'high',
        'urgent' => 'urgent',
        'critical' => 'critical',
    ],

    'priority_labels' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
        'critical' => 'Critical',
    ],

    'priority_colors' => [
        'low' => 'secondary',
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
        'critical' => 'dark',
    ],

];
