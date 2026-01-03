<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                // AUDIT FIX: Add proper error logging
                Log::error('Notification channel failed', [
                    'channel' => $channel,
                    'type' => $type,
                    'recipient' => $this->getRecipientIdentifier($recipient),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

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
            // Document notifications
            'document_uploaded' => [
                'subject' => 'Document Uploaded - {{document_type}}',
                'message' => 'Dear {{candidate_name}}, a new document ({{document_name}}) has been uploaded on {{uploaded_date}}. Expiry: {{expiry_date}}.',
            ],
            // Training notifications
            'training_completed' => [
                'subject' => 'Training Completed Successfully',
                'message' => 'Congratulations {{candidate_name}}! You have successfully completed your training in {{trade_name}} at {{campus_name}} on {{completion_date}}.',
            ],
            // Departure notifications
            'departure_confirmed' => [
                'subject' => 'Departure Confirmed',
                'message' => 'Dear {{candidate_name}}, your departure has been confirmed for {{departure_date}}. Flight: {{flight_number}}. Destination: {{destination}}.',
            ],
            'iqama_recorded' => [
                'subject' => 'Iqama Registration Confirmed',
                'message' => 'Dear {{candidate_name}}, your Iqama has been registered. Iqama Number: {{iqama_number}}. Date: {{iqama_date}}.',
            ],
            'first_salary_confirmed' => [
                'subject' => 'First Salary Confirmed',
                'message' => 'Congratulations {{candidate_name}}! Your first salary has been confirmed on {{salary_date}} from {{employer}}.',
            ],
            'compliance_achieved' => [
                'subject' => '90-Day Compliance Achieved',
                'message' => 'Congratulations {{candidate_name}}! You have successfully completed your 90-day compliance period on {{compliance_date}}.',
            ],
            'issue_reported' => [
                'subject' => 'Issue Reported - {{issue_type}}',
                'message' => 'An issue has been reported for {{candidate_name}}: {{issue_description}}. Reported on {{reported_date}}.',
            ],
            // Complaint notifications
            'complaint_registered' => [
                'subject' => 'Complaint Registered - {{complaint_reference}}',
                'message' => 'Dear {{candidate_name}}, your complaint (Ref: {{complaint_reference}}) in category {{category}} has been registered on {{submitted_date}}. We will process it shortly.',
            ],
            'complaint_assigned' => [
                'subject' => 'New Complaint Assigned - {{complaint_reference}}',
                'message' => 'Dear {{user_name}}, a new complaint (Ref: {{complaint_reference}}) has been assigned to you. Category: {{category}}. Priority: {{priority}}. Complainant: {{complainant_name}}.',
            ],
            // Visa notifications
            'visa_process_initiated' => [
                'subject' => 'Visa Process Initiated',
                'message' => 'Dear {{candidate_name}}, your visa process has been initiated on {{initiated_date}} for {{trade_name}}. You will be notified of each step.',
            ],
            'visa_stage_completed' => [
                'subject' => 'Visa Stage Completed - {{stage_name}}',
                'message' => 'Dear {{candidate_name}}, the {{stage_name}} stage of your visa process has been completed on {{completion_date}}.',
            ],
            'ticket_uploaded' => [
                'subject' => 'Travel Ticket Uploaded',
                'message' => 'Dear {{candidate_name}}, your travel ticket has been uploaded on {{upload_date}}. Flight: {{flight_number}}.',
            ],
            'visa_process_completed' => [
                'subject' => 'Visa Process Completed',
                'message' => 'Congratulations {{candidate_name}}! Your visa process has been completed on {{completion_date}}. PTN: {{ptn_number}}. Visa: {{visa_number}}.',
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
     *
     * AUDIT FIX: Throws exception until SMS gateway is properly integrated.
     * Previously returned fake success which could cause missed critical notifications.
     */
    private function sendSMS($recipient, $notificationData)
    {
        $phone = is_object($recipient) ? $recipient->phone : $recipient;

        if (empty($phone)) {
            throw new \Exception('Phone number not provided');
        }

        // Format phone for SMS gateway
        $phone = $this->formatPhoneNumber($phone);

        // Check if SMS gateway is configured
        if (!config('services.sms.enabled', false)) {
            // Log the attempt for audit purposes
            Log::warning('SMS notification attempted but gateway not configured', [
                'phone' => $phone,
                'type' => $notificationData['type'] ?? 'unknown',
            ]);

            throw new \Exception('SMS gateway not configured. Please configure SMS service in config/services.php');
        }

        // TODO: Integrate with SMS gateway (e.g., Twilio, Nexmo, local SMS provider)
        // Example implementation:
        // $smsGateway = app(SmsGatewayInterface::class);
        // $result = $smsGateway->send($phone, $notificationData['message']);

        throw new \Exception('SMS gateway integration not yet implemented. Contact system administrator.');
    }

    /**
     * Send WhatsApp notification
     *
     * AUDIT FIX: Throws exception until WhatsApp API is properly integrated.
     * Previously returned fake success which could cause missed critical notifications.
     */
    private function sendWhatsApp($recipient, $notificationData)
    {
        $phone = is_object($recipient) ? $recipient->phone : $recipient;

        if (empty($phone)) {
            throw new \Exception('Phone number not provided');
        }

        // Format phone for WhatsApp
        $phone = $this->formatPhoneNumber($phone);

        // Check if WhatsApp API is configured
        if (!config('services.whatsapp.enabled', false)) {
            // Log the attempt for audit purposes
            Log::warning('WhatsApp notification attempted but API not configured', [
                'phone' => $phone,
                'type' => $notificationData['type'] ?? 'unknown',
            ]);

            throw new \Exception('WhatsApp API not configured. Please configure WhatsApp Business API in config/services.php');
        }

        // TODO: Integrate with WhatsApp Business API
        // Example implementation:
        // $whatsappClient = app(WhatsAppBusinessClient::class);
        // $result = $whatsappClient->sendMessage($phone, $notificationData['message']);

        throw new \Exception('WhatsApp API integration not yet implemented. Contact system administrator.');
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
                // AUDIT FIX: Add proper error logging for bulk send failures
                Log::error('Bulk notification failed for recipient', [
                    'type' => $type,
                    'recipient' => $this->getRecipientIdentifier($recipient),
                    'error' => $e->getMessage(),
                ]);

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
                // AUDIT FIX: Add proper error logging for scheduled notification failures
                Log::error('Scheduled notification failed', [
                    'notification_id' => $notification->id,
                    'type' => $notification->type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

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

    // ==================== DOCUMENT ARCHIVE NOTIFICATIONS ====================

    /**
     * Send notification when document is uploaded
     */
    public function sendDocumentUploaded($document): array
    {
        $candidate = $document->candidate;

        if (!$candidate) {
            // If document is not linked to a candidate, log and return
            activity()
                ->withProperties(['document_id' => $document->id])
                ->log('Document uploaded notification skipped - no candidate linked');

            return ['success' => true, 'skipped' => 'No candidate linked to document'];
        }

        $data = [
            'candidate_name' => $candidate->name,
            'document_type' => $document->document_type,
            'document_name' => $document->document_name,
            'uploaded_date' => now()->format('M d, Y'),
            'expiry_date' => $document->expiry_date ? $document->expiry_date->format('M d, Y') : 'N/A',
        ];

        return $this->send($candidate, 'document_uploaded', $data, ['email', 'in_app']);
    }

    // ==================== TRAINING NOTIFICATIONS ====================

    /**
     * Send notification when candidate is assigned to training batch
     */
    public function sendTrainingAssigned($candidate, $batch): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'batch_code' => $batch->batch_code ?? 'N/A',
            'trade_name' => $batch->trade->name ?? 'N/A',
            'start_date' => $batch->start_date ? $batch->start_date->format('M d, Y') : 'TBA',
            'campus_name' => $batch->campus->name ?? 'N/A',
            'location' => $batch->campus->address ?? 'N/A',
        ];

        return $this->send($candidate, 'training_started', $data, ['email', 'sms']);
    }

    /**
     * Send notification when certificate is issued
     */
    public function sendCertificateIssued($candidate): array
    {
        $certificate = $candidate->certificate ?? $candidate->trainingCertificates()->latest()->first();

        $data = [
            'candidate_name' => $candidate->name,
            'certificate_number' => $certificate ? $certificate->certificate_number : 'N/A',
            'campus_name' => $candidate->campus->name ?? 'N/A',
            'trade_name' => $candidate->trade->name ?? 'N/A',
            'issue_date' => $certificate && $certificate->issue_date ? $certificate->issue_date->format('M d, Y') : now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'certificate_issued', $data, ['email', 'sms']);
    }

    /**
     * Send notification when training is completed
     */
    public function sendTrainingCompleted($candidate): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'trade_name' => $candidate->trade->name ?? 'N/A',
            'campus_name' => $candidate->campus->name ?? 'N/A',
            'completion_date' => now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'training_completed', $data, ['email', 'sms']);
    }

    // ==================== DEPARTURE NOTIFICATIONS ====================

    /**
     * Send notification when pre-departure briefing is completed
     */
    public function sendBriefingCompleted($candidate): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'briefing_date' => now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'departure_briefing', $data, ['email', 'sms']);
    }

    /**
     * Send notification when departure is confirmed
     */
    public function sendDepartureConfirmed($candidate): array
    {
        $departure = $candidate->departure;

        $data = [
            'candidate_name' => $candidate->name,
            'departure_date' => $departure && $departure->departure_date ? $departure->departure_date->format('M d, Y') : 'TBA',
            'flight_number' => $departure->flight_number ?? 'TBA',
            'destination' => $departure->destination ?? 'Saudi Arabia',
        ];

        return $this->send($candidate, 'departure_confirmed', $data, ['email', 'sms', 'whatsapp']);
    }

    /**
     * Send notification when Iqama is recorded
     */
    public function sendIqamaRecorded($candidate): array
    {
        $departure = $candidate->departure;

        $data = [
            'candidate_name' => $candidate->name,
            'iqama_number' => $departure->iqama_number ?? 'N/A',
            'iqama_date' => $departure && $departure->iqama_date ? $departure->iqama_date->format('M d, Y') : now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'iqama_recorded', $data, ['email', 'sms']);
    }

    /**
     * Send notification when first salary is confirmed
     */
    public function sendFirstSalaryConfirmed($candidate): array
    {
        $departure = $candidate->departure;

        $data = [
            'candidate_name' => $candidate->name,
            'salary_date' => $departure && $departure->first_salary_date ? $departure->first_salary_date->format('M d, Y') : now()->format('M d, Y'),
            'employer' => $departure->employer_name ?? 'N/A',
        ];

        return $this->send($candidate, 'first_salary_confirmed', $data, ['email', 'sms']);
    }

    /**
     * Send notification when 90-day compliance is achieved
     */
    public function sendComplianceAchieved($candidate): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'compliance_date' => now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'compliance_achieved', $data, ['email', 'sms']);
    }

    /**
     * Send notification when an issue is reported for a candidate
     */
    public function sendIssueReported($candidate, $issue): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'issue_type' => $issue['type'] ?? 'General',
            'issue_description' => $issue['description'] ?? 'An issue has been reported',
            'reported_date' => now()->format('M d, Y'),
        ];

        // Send to candidate and admins
        return $this->send($candidate, 'issue_reported', $data, ['email', 'in_app']);
    }

    // ==================== COMPLAINT NOTIFICATIONS ====================

    /**
     * Send notification when complaint is registered
     */
    public function sendComplaintRegistered($complaint): array
    {
        $candidate = $complaint->candidate;
        $recipient = $candidate ?? $complaint->complainant_email;

        $data = [
            'candidate_name' => $candidate ? $candidate->name : $complaint->complainant_name,
            'complaint_reference' => $complaint->complaint_reference ?? $complaint->id,
            'category' => $complaint->category ?? 'General',
            'submitted_date' => $complaint->created_at->format('M d, Y'),
        ];

        return $this->send($recipient, 'complaint_registered', $data, ['email']);
    }

    /**
     * Send notification when complaint is assigned to a user
     */
    public function sendComplaintAssigned($complaint, $assignedUser): array
    {
        $data = [
            'user_name' => $assignedUser->name,
            'complaint_reference' => $complaint->complaint_reference ?? $complaint->id,
            'category' => $complaint->category ?? 'General',
            'priority' => $complaint->priority ?? 'Normal',
            'complainant_name' => $complaint->candidate ? $complaint->candidate->name : $complaint->complainant_name,
        ];

        return $this->send($assignedUser, 'complaint_assigned', $data, ['email', 'in_app']);
    }

    /**
     * Send notification when complaint is escalated
     */
    public function sendComplaintEscalated($complaint): array
    {
        $candidate = $complaint->candidate;
        $recipient = $candidate ?? $complaint->complainant_email;

        $data = [
            'candidate_name' => $candidate ? $candidate->name : $complaint->complainant_name,
            'complaint_reference' => $complaint->complaint_reference ?? $complaint->id,
            'status' => 'Escalated',
            'update_message' => 'Your complaint has been escalated for priority attention.',
        ];

        return $this->send($recipient, 'complaint_update', $data, ['email']);
    }

    /**
     * Send notification when complaint is resolved
     */
    public function sendComplaintResolved($complaint): array
    {
        $candidate = $complaint->candidate;
        $recipient = $candidate ?? $complaint->complainant_email;

        $data = [
            'candidate_name' => $candidate ? $candidate->name : $complaint->complainant_name,
            'complaint_reference' => $complaint->complaint_reference ?? $complaint->id,
            'status' => 'Resolved',
            'update_message' => $complaint->resolution_notes ?? 'Your complaint has been resolved.',
        ];

        return $this->send($recipient, 'complaint_update', $data, ['email', 'sms']);
    }

    /**
     * Send notification when complaint is closed
     */
    public function sendComplaintClosed($complaint): array
    {
        $candidate = $complaint->candidate;
        $recipient = $candidate ?? $complaint->complainant_email;

        $data = [
            'candidate_name' => $candidate ? $candidate->name : $complaint->complainant_name,
            'complaint_reference' => $complaint->complaint_reference ?? $complaint->id,
            'status' => 'Closed',
            'update_message' => 'Your complaint has been closed. Thank you for your feedback.',
        ];

        return $this->send($recipient, 'complaint_update', $data, ['email']);
    }

    // ==================== VISA PROCESSING NOTIFICATIONS ====================

    /**
     * Send notification when visa process is initiated
     */
    public function sendVisaProcessInitiated($candidate): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'initiated_date' => now()->format('M d, Y'),
            'trade_name' => $candidate->trade->name ?? 'N/A',
        ];

        return $this->send($candidate, 'visa_process_initiated', $data, ['email', 'sms']);
    }

    /**
     * Send notification when a visa stage is completed
     */
    public function sendVisaStageCompleted($candidate, string $stage): array
    {
        $data = [
            'candidate_name' => $candidate->name,
            'stage_name' => $stage,
            'completion_date' => now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'visa_stage_completed', $data, ['email', 'sms']);
    }

    /**
     * Send notification when visa is issued
     */
    public function sendVisaIssued($candidate): array
    {
        $visaProcess = $candidate->visaProcess;

        $data = [
            'candidate_name' => $candidate->name,
            'visa_number' => $visaProcess->visa_number ?? 'N/A',
            'ptn_number' => $visaProcess->ptn_number ?? 'N/A',
            'issue_date' => $visaProcess && $visaProcess->visa_issue_date ? $visaProcess->visa_issue_date->format('M d, Y') : now()->format('M d, Y'),
        ];

        return $this->send($candidate, 'visa_approved', $data, ['email', 'sms', 'whatsapp']);
    }

    /**
     * Send notification when ticket is uploaded
     */
    public function sendTicketUploaded($candidate): array
    {
        $visaProcess = $candidate->visaProcess;

        $data = [
            'candidate_name' => $candidate->name,
            'upload_date' => now()->format('M d, Y'),
            'flight_number' => $visaProcess->flight_number ?? 'TBA',
        ];

        return $this->send($candidate, 'ticket_uploaded', $data, ['email', 'sms']);
    }

    /**
     * Send notification when visa process is completed
     */
    public function sendVisaProcessCompleted($candidate): array
    {
        $visaProcess = $candidate->visaProcess;

        $data = [
            'candidate_name' => $candidate->name,
            'completion_date' => now()->format('M d, Y'),
            'ptn_number' => $visaProcess->ptn_number ?? 'N/A',
            'visa_number' => $visaProcess->visa_number ?? 'N/A',
        ];

        return $this->send($candidate, 'visa_process_completed', $data, ['email', 'sms', 'whatsapp']);
    }
}