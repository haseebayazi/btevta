<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Notification types
     */
    const TYPES = [
        'screening_scheduled' => 'Screening Scheduled',
        'registration_complete' => 'Registration Complete',
        'training_started' => 'Training Started',
        'assessment_scheduled' => 'Assessment Scheduled',
        'certificate_issued' => 'Certificate Issued',
        'visa_interview' => 'Visa Interview',
        'visa_approved' => 'Visa Approved',
        'departure_briefing' => 'Departure Briefing',
        'departure_reminder' => 'Departure Reminder',
        'compliance_reminder' => 'Compliance Reminder',
        'document_expiry' => 'Document Expiry Alert',
        'complaint_update' => 'Complaint Status Update',
        'sla_breach' => 'SLA Breach Alert',
        'remittance_recorded' => 'New Remittance Recorded',
        'remittance_verified' => 'Remittance Verified',
        'remittance_proof_missing' => 'Remittance Proof Missing',
        'remittance_alert_critical' => 'Critical Remittance Alert',
        'remittance_alert_resolved' => 'Remittance Alert Resolved',
        'first_remittance_received' => 'First Remittance Received',
        'remittance_monthly_summary' => 'Monthly Remittance Summary',
    ];

    /**
     * Notification channels
     */
    const CHANNELS = [
        'email' => 'Email',
        'sms' => 'SMS',
        'whatsapp' => 'WhatsApp',
        'in_app' => 'In-App Notification',
    ];

    /**
     * Get notification types
     */
    public function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Get channels
     */
    public function getChannels(): array
    {
        return self::CHANNELS;
    }

    /**
     * Send single notification
     */
    public function send($recipient, string $type, array $data = [], array $channels = ['email']): array
    {
        // Prepare notification data
        $notificationData = $this->prepareNotificationData($type, $data);
        
        // Send through each channel
        $results = [];
        foreach ($channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        $results['email'] = $this->sendEmail($recipient, $notificationData);
                        break;
                    case 'sms':
                        $results['sms'] = $this->sendSMS($recipient, $notificationData);
                        break;
                    case 'whatsapp':
                        $results['whatsapp'] = $this->sendWhatsApp($recipient, $notificationData);
                        break;
                    case 'in_app':
                        $results['in_app'] = $this->sendInApp($recipient, $notificationData);
                        break;
                }
            } catch (\Exception $e) {
                $results[$channel] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log notification
        $this->logNotification($recipient, $type, $channels, $results);

        return $results;
    }

    /**
     * Prepare notification data based on type
     */
    private function prepareNotificationData(string $type, array $data): array
    {
        $templates = $this->getTemplates();
        $template = $templates[$type] ?? $templates['default'];
        
        return [
            'type' => $type,
            'subject' => $this->replacePlaceholders($template['subject'], $data),
            'message' => $this->replacePlaceholders($template['message'], $data),
            'data' => $data,
        ];
    }

    /**
     * Replace placeholders in template
     */
    private function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace("{{{$key}}}", $value, $text);
            }
        }
        return $text;
    }

    /**
     * Get notification templates
     */
    private function getTemplates(): array
    {
        return [
            'screening_scheduled' => [
                'subject' => 'Screening Scheduled - {{candidate_name}}',
                'message' => 'Dear {{candidate_name}}, your screening has been scheduled for {{date}} at {{location}}. Please bring your documents. Call: {{contact_number}}',
            ],
            'registration_complete' => [
                'subject' => 'Registration Completed Successfully',
                'message' => 'Dear {{candidate_name}}, your registration has been completed. Registration Number: {{registration_number}}. Campus: {{campus_name}}',
            ],
            'training_started' => [
                'subject' => 'Training Program Started',
                'message' => 'Dear {{candidate_name}}, your training program has started on {{start_date}}. Please attend regularly. Campus: {{campus_name}}',
            ],
            'assessment_scheduled' => [
                'subject' => 'Assessment Scheduled - {{assessment_type}}',
                'message' => 'Dear {{candidate_name}}, your {{assessment_type}} has been scheduled for {{assessment_date}}. Location: {{location}}',
            ],
            'certificate_issued' => [
                'subject' => 'Training Certificate Issued',
                'message' => 'Dear {{candidate_name}}, congratulations! Your training certificate ({{certificate_number}}) has been issued. You can collect it from {{campus_name}}.',
            ],
            'visa_interview' => [
                'subject' => 'Visa Interview Scheduled',
                'message' => 'Dear {{candidate_name}}, your visa interview has been scheduled for {{interview_date}} at {{interview_location}}. Please be on time with all required documents.',
            ],
            'visa_approved' => [
                'subject' => 'Visa Approved - PTN Issued',
                'message' => 'Dear {{candidate_name}}, congratulations! Your visa has been approved. PTN Number: {{ptn_number}}. Please contact for next steps.',
            ],
            'departure_briefing' => [
                'subject' => 'Pre-Departure Briefing Scheduled',
                'message' => 'Dear {{candidate_name}}, your pre-departure briefing is scheduled for {{briefing_date}}. Attendance is mandatory. Location: {{location}}',
            ],
            'departure_reminder' => [
                'subject' => 'Departure Reminder - {{days_to_departure}} Days',
                'message' => 'Dear {{candidate_name}}, your departure is in {{days_to_departure}} days on {{departure_date}}. Flight: {{flight_number}}. Ensure all documents are ready.',
            ],
            'compliance_reminder' => [
                'subject' => 'Post-Arrival Compliance Reminder',
                'message' => 'Dear {{candidate_name}}, please update your compliance information: {{pending_items}}. Deadline: {{days_remaining}} days.',
            ],
            'document_expiry' => [
                'subject' => 'Document Expiry Alert - {{document_type}}',
                'message' => 'Alert: Your {{document_type}} will expire in {{days_until_expiry}} days on {{expiry_date}}. Please renew immediately.',
            ],
            'complaint_update' => [
                'subject' => 'Complaint Status Update - {{complaint_reference}}',
                'message' => 'Your complaint {{complaint_reference}} status has been updated to: {{status}}. {{update_message}}',
            ],
            'sla_breach' => [
                'subject' => 'SLA Breach Alert - {{item_type}}',
                'message' => 'ALERT: {{item_type}} {{item_reference}} has breached SLA by {{days_overdue}} days. Immediate action required.',
            ],
            'remittance_recorded' => [
                'subject' => 'New Remittance Recorded - PKR {{amount}}',
                'message' => 'Dear {{candidate_name}}, a new remittance of PKR {{amount}} has been recorded on {{transfer_date}}. Transaction Ref: {{transaction_reference}}. Purpose: {{purpose}}. Thank you for supporting your family.',
            ],
            'remittance_verified' => [
                'subject' => 'Remittance Verified - PKR {{amount}}',
                'message' => 'Good news! Your remittance of PKR {{amount}} (Ref: {{transaction_reference}}) has been verified on {{verification_date}}. Status: Approved.',
            ],
            'remittance_proof_missing' => [
                'subject' => 'Action Required: Upload Remittance Proof',
                'message' => 'Dear {{candidate_name}}, your remittance of PKR {{amount}} (Ref: {{transaction_reference}}) is missing proof documentation. Please upload the proof within {{days_remaining}} days to avoid complications.',
            ],
            'remittance_alert_critical' => [
                'subject' => 'URGENT: Critical Remittance Alert',
                'message' => 'URGENT ALERT: {{alert_title}}. Details: {{alert_message}}. Immediate action required. Contact your campus administrator.',
            ],
            'remittance_alert_resolved' => [
                'subject' => 'Remittance Alert Resolved',
                'message' => 'Good news! The remittance alert "{{alert_title}}" has been resolved. Resolution: {{resolution_notes}}. Thank you for your cooperation.',
            ],
            'first_remittance_received' => [
                'subject' => 'Congratulations! First Remittance Received',
                'message' => 'Congratulations {{candidate_name}}! Your first remittance of PKR {{amount}} has been recorded on {{transfer_date}}. This is an important milestone. Keep supporting your family back home!',
            ],
            'remittance_monthly_summary' => [
                'subject' => 'Monthly Remittance Summary - {{month}} {{year}}',
                'message' => 'Dear {{candidate_name}}, your remittance summary for {{month}} {{year}}: Total remittances: {{count}}, Total amount: PKR {{total_amount}}, Average: PKR {{average_amount}}. Keep up the good work!',
            ],
            'default' => [
                'subject' => 'Notification from BTEVTA System',
                'message' => '{{message}}',
            ],
        ];
    }

    /**
     * Send email notification
     */
    private function sendEmail($recipient, array $notificationData): array
    {
        $email = is_object($recipient) ? $recipient->email : $recipient;
        
        if (empty($email)) {
            throw new \Exception('Email address not provided');
        }

        Mail::raw($notificationData['message'], function ($message) use ($email, $notificationData) {
            $message->to($email)
                    ->subject($notificationData['subject']);
        });

        return [
            'success' => true,
            'channel' => 'email',
            'recipient' => $email,
            'sent_at' => now(),
        ];
    }

    /**
     * Send SMS notification
     */
    private function sendSMS($recipient, $notificationData)
    {
        $phone = is_object($recipient) ? $recipient->phone : $recipient;
        
        if (empty($phone)) {
            throw new \Exception('Phone number not provided');
        }

        // Format phone for SMS gateway
        $phone = $this->formatPhoneNumber($phone);
        
        // Here you would integrate with SMS gateway (e.g., Twilio, Nexmo, local SMS provider)
        // For now, we'll just log it
        
        // Example: $this->smsGateway->send($phone, $notificationData['message']);
        
        activity()
            ->withProperties([
                'phone' => $phone,
                'message' => $notificationData['message'],
            ])
            ->log('SMS sent');

        return [
            'success' => true,
            'channel' => 'sms',
            'recipient' => $phone,
            'sent_at' => now(),
            'note' => 'SMS gateway integration pending',
        ];
    }

    /**
     * Send WhatsApp notification
     */
    private function sendWhatsApp($recipient, $notificationData)
    {
        $phone = is_object($recipient) ? $recipient->phone : $recipient;
        
        if (empty($phone)) {
            throw new \Exception('Phone number not provided');
        }

        // Format phone for WhatsApp
        $phone = $this->formatPhoneNumber($phone);
        
        // Here you would integrate with WhatsApp Business API
        // For now, we'll just log it
        
        activity()
            ->withProperties([
                'phone' => $phone,
                'message' => $notificationData['message'],
            ])
            ->log('WhatsApp message queued');

        return [
            'success' => true,
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'sent_at' => now(),
            'note' => 'WhatsApp API integration pending',
        ];
    }

    /**
     * Send in-app notification
     */
    private function sendInApp($recipient, $notificationData)
    {
        if (!is_object($recipient) || !method_exists($recipient, 'notify')) {
            throw new \Exception('Recipient must be a notifiable model');
        }

        // Use Laravel's notification system
        // You would create a notification class first
        // For now, we'll use database notifications
        
        $recipient->notifications()->create([
            'type' => 'App\Notifications\GeneralNotification',
            'data' => $notificationData,
            'read_at' => null,
        ]);

        return [
            'success' => true,
            'channel' => 'in_app',
            'recipient' => $recipient->id,
            'sent_at' => now(),
        ];
    }

    /**
     * Format phone number for international format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming Pakistan +92)
        if (!str_starts_with($phone, '92') && !str_starts_with($phone, '+92')) {
            if (str_starts_with($phone, '0')) {
                $phone = '92' . substr($phone, 1);
            } else {
                $phone = '92' . $phone;
            }
        }
        
        return '+' . ltrim($phone, '+');
    }

    /**
     * Bulk send notifications
     */
    public function bulkSend(array $recipients, string $type, array $data = [], array $channels = ['email']): array
    {
        $results = [
            'total' => count($recipients),
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($recipients as $recipient) {
            try {
                // Personalize data for each recipient
                $personalizedData = $this->personalizeData($data, $recipient);
                
                $result = $this->send($recipient, $type, $personalizedData, $channels);
                
                $results['successful']++;
                $results['details'][] = [
                    'recipient' => $this->getRecipientIdentifier($recipient),
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'recipient' => $this->getRecipientIdentifier($recipient),
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log bulk send
        activity()
            ->withProperties($results)
            ->log("Bulk notification sent: {$type}");

        return $results;
    }

    /**
     * Personalize data for individual recipient
     */
    private function personalizeData(array $data, $recipient): array
    {
        if (is_object($recipient)) {
            $personalData = [
                'candidate_name' => $recipient->name ?? 'Candidate',
                'email' => $recipient->email ?? '',
                'phone' => $recipient->phone ?? '',
            ];
            
            return array_merge($data, $personalData);
        }
        
        return $data;
    }

    /**
     * Get recipient identifier
     */
    private function getRecipientIdentifier($recipient)
    {
        if (is_object($recipient)) {
            return $recipient->email ?? $recipient->phone ?? $recipient->id ?? 'Unknown';
        }
        return $recipient;
    }

    /**
     * Send scheduled notifications
     */
    public function sendScheduled($scheduledFor, $recipient, string $type, array $data = [], array $channels = ['email']): array
    {
        // Store in database for scheduled sending
        DB::table('scheduled_notifications')->insert([
            'recipient_type' => is_object($recipient) ? get_class($recipient) : 'string',
            'recipient_id' => is_object($recipient) ? $recipient->id : null,
            'recipient_value' => is_object($recipient) ? null : $recipient,
            'type' => $type,
            'data' => json_encode($data),
            'channels' => json_encode($channels),
            'scheduled_for' => $scheduledFor,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        return [
            'success' => true,
            'scheduled_for' => $scheduledFor,
            'message' => 'Notification scheduled successfully',
        ];
    }

    /**
     * Process scheduled notifications (called by cron job)
     */
    public function processScheduled(): array
    {
        $pending = DB::table('scheduled_notifications')
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->get();

        $processed = 0;
        foreach ($pending as $notification) {
            try {
                $recipient = $this->resolveRecipient($notification);

                // JSON ERROR HANDLING: Safely decode JSON data
                $data = json_decode($notification->data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
                }

                $channels = json_decode($notification->channels, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON channels: ' . json_last_error_msg());
                }

                $this->send($recipient, $notification->type, $data, $channels);
                
                DB::table('scheduled_notifications')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                
                $processed++;
            } catch (\Exception $e) {
                DB::table('scheduled_notifications')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'failed_at' => now(),
                    ]);
            }
        }

        return [
            'total' => $pending->count(),
            'processed' => $processed,
        ];
    }

    /**
     * Resolve recipient from scheduled notification
     * SECURITY FIX: Added whitelist validation for dynamic class loading
     */
    private function resolveRecipient($notification)
    {
        if ($notification->recipient_type !== 'string') {
            // Whitelist of allowed recipient types for security
            $allowedClasses = [
                'App\\Models\\User',
                'App\\Models\\Candidate',
                'App\\Models\\Campus',
            ];

            if (!in_array($notification->recipient_type, $allowedClasses)) {
                throw new \Exception('Invalid recipient type: Security violation');
            }

            $class = $notification->recipient_type;
            return $class::find($notification->recipient_id);
        }

        return $notification->recipient_value;
    }

    /**
     * Log notification
     */
    private function logNotification($recipient, $type, $channels, $results)
    {
        activity()
            ->withProperties([
                'recipient' => $this->getRecipientIdentifier($recipient),
                'type' => $type,
                'channels' => $channels,
                'results' => $results,
            ])
            ->log("Notification sent: {$type}");
    }

    /**
     * Send training reminders
     */
    public function sendTrainingReminders($batch)
    {
        $candidates = $batch->candidates;
        
        $data = [
            'batch_code' => $batch->batch_code,
            'start_date' => $batch->start_date->format('Y-m-d'),
            'campus_name' => $batch->campus->name,
            'location' => $batch->campus->address,
        ];

        return $this->bulkSend($candidates, 'training_started', $data, ['email', 'sms']);
    }

    /**
     * Send departure reminders
     */
    public function sendDepartureReminders(int $daysBefore = 7): array
    {
        $upcomingDepartures = DB::table('departures')
            ->join('candidates', 'departures.candidate_id', '=', 'candidates.id')
            ->whereDate('departures.departure_date', '=', Carbon::now()->addDays($daysBefore))
            ->select('candidates.*', 'departures.*')
            ->get();

        $results = [];
        foreach ($upcomingDepartures as $departure) {
            $candidate = Candidate::find($departure->candidate_id);

            // NULL CHECK: Skip if candidate not found
            if (!$candidate) {
                \Log::warning("Candidate not found for departure reminder", ['candidate_id' => $departure->candidate_id]);
                continue;
            }

            $data = [
                'candidate_name' => $candidate->name,
                'departure_date' => $departure->departure_date,
                'days_to_departure' => $daysBefore,
                'flight_number' => $departure->flight_number ?? 'TBA',
            ];

            $results[] = $this->send($candidate, 'departure_reminder', $data, ['email', 'sms', 'whatsapp']);
        }

        return $results;
    }

    /**
     * Send compliance reminders
     */
    public function sendComplianceReminders(): array
    {
        $departureService = new DepartureService();
        $pendingCompliance = $departureService->getPendingComplianceItems();

        $results = [];
        foreach ($pendingCompliance as $item) {
            $candidate = $item['candidate'];
            $pendingItems = implode(', ', array_keys($item['pending_items']));
            
            $data = [
                'candidate_name' => $candidate->name,
                'pending_items' => $pendingItems,
                'days_remaining' => $item['days_remaining'],
            ];

            $results[] = $this->send($candidate, 'compliance_reminder', $data, ['email', 'sms', 'whatsapp']);
        }

        return $results;
    }

    /**
     * Send document expiry alerts
     */
    public function sendDocumentExpiryAlerts(int $days = 30): array
    {
        $documentService = new DocumentArchiveService();
        $expiringDocs = $documentService->getExpiringDocuments($days);

        $results = [];
        foreach ($expiringDocs as $item) {
            $document = $item['document'];
            $candidate = $document->candidate;
            
            if (!$candidate) continue;
            
            $data = [
                'candidate_name' => $candidate->name,
                'document_type' => $document->document_type,
                'expiry_date' => $document->expiry_date,
                'days_until_expiry' => $item['days_until_expiry'],
            ];

            $results[] = $this->send($candidate, 'document_expiry', $data, ['email', 'sms']);
        }

        return $results;
    }

    /**
     * Send complaint update notification
     */
    public function sendComplaintUpdate($complaint, string $updateMessage): array
    {
        $candidate = $complaint->candidate;
        
        $data = [
            'candidate_name' => $candidate ? $candidate->name : $complaint->complainant_name,
            'complaint_reference' => $complaint->complaint_reference,
            'status' => $complaint->status,
            'update_message' => $updateMessage,
        ];

        // Send to complainant
        $recipient = $candidate ?? $complaint->complainant_email;
        
        return $this->send($recipient, 'complaint_update', $data, ['email']);
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(array $filters = []): array
    {
        // This would query a notifications log table
        // For now, return from activity log
        
        $query = DB::table('activity_log')
            ->where('description', 'like', 'Notification sent:%');

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $total = $query->count();
        $logs = $query->get();

        return [
            'total_sent' => $total,
            'by_type' => $logs->groupBy(function($log) {
                preg_match('/Notification sent: (.+)/', $log->description, $matches);
                return $matches[1] ?? 'unknown';
            })->map->count(),
            'recent' => $query->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
    }

    /**
     * Send remittance recorded notification
     */
    public function sendRemittanceRecorded($remittance): array
    {
        $candidate = $remittance->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'amount' => number_format($remittance->amount, 2),
            'transfer_date' => $remittance->transfer_date->format('M d, Y'),
            'transaction_reference' => $remittance->transaction_reference ?? 'N/A',
            'purpose' => ucwords(str_replace('_', ' ', $remittance->primary_purpose)),
        ];

        // Send to candidate with email and SMS
        return $this->send($candidate, 'remittance_recorded', $data, ['email', 'sms']);
    }

    /**
     * Send remittance verified notification
     */
    public function sendRemittanceVerified($remittance): array
    {
        $candidate = $remittance->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'amount' => number_format($remittance->amount, 2),
            'transaction_reference' => $remittance->transaction_reference ?? 'N/A',
            'verification_date' => $remittance->verified_at ? $remittance->verified_at->format('M d, Y') : now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'remittance_verified', $data, ['email', 'sms']);
    }

    /**
     * Send proof missing notification
     */
    public function sendRemittanceProofMissing($remittance, int $daysRemaining = 7): array
    {
        $candidate = $remittance->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'amount' => number_format($remittance->amount, 2),
            'transaction_reference' => $remittance->transaction_reference ?? 'N/A',
            'days_remaining' => $daysRemaining,
        ];

        return $this->send($candidate, 'remittance_proof_missing', $data, ['email', 'sms', 'whatsapp']);
    }

    /**
     * Send critical remittance alert notification
     */
    public function sendRemittanceAlertCritical($alert): array
    {
        $candidate = $alert->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'alert_title' => $alert->title,
            'alert_message' => $alert->message,
        ];

        // Critical alerts go through all channels
        return $this->send($candidate, 'remittance_alert_critical', $data, ['email', 'sms', 'whatsapp', 'in_app']);
    }

    /**
     * Send alert resolved notification
     */
    public function sendRemittanceAlertResolved($alert): array
    {
        $candidate = $alert->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'alert_title' => $alert->title,
            'resolution_notes' => $alert->resolution_notes ?? 'Alert has been resolved',
        ];

        return $this->send($candidate, 'remittance_alert_resolved', $data, ['email', 'in_app']);
    }

    /**
     * Send first remittance notification (special congratulations)
     */
    public function sendFirstRemittanceReceived($remittance): array
    {
        $candidate = $remittance->candidate;

        if (!$candidate) {
            return ['success' => false, 'error' => 'Candidate not found'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'amount' => number_format($remittance->amount, 2),
            'transfer_date' => $remittance->transfer_date->format('M d, Y'),
        ];

        // Special notification - send through all channels
        return $this->send($candidate, 'first_remittance_received', $data, ['email', 'sms', 'whatsapp']);
    }

    /**
     * Send monthly remittance summary
     */
    public function sendRemittanceMonthlySummary($candidate, $month, $year, $stats): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'month' => $month,
            'year' => $year,
            'count' => $stats['count'],
            'total_amount' => number_format($stats['total_amount'], 2),
            'average_amount' => number_format($stats['average_amount'], 2),
        ];

        return $this->send($candidate, 'remittance_monthly_summary', $data, ['email']);
    }

    /**
     * Send bulk remittance notifications to multiple candidates
     */
    public function sendBulkRemittanceNotifications($candidates, string $notificationType, array $customData = []): array
    {
        return $this->bulkSend($candidates, $notificationType, $customData, ['email', 'sms']);
    }
}