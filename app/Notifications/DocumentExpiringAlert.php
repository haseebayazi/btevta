<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DocumentArchive;

class DocumentExpiringAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;
    protected $daysRemaining;

    public function __construct(DocumentArchive $document, $daysRemaining)
    {
        $this->document = $document;
        $this->daysRemaining = $daysRemaining;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $severity = $this->getSeverity();
        $urgencyText = $this->getUrgencyText();

        $message = (new MailMessage)
            ->subject("[{$severity}] Document Expiry Alert - {$this->document->document_name}")
            ->greeting("Document Expiry Alert")
            ->line("The following document is expiring soon and requires immediate attention:");

        // Document details
        $message->line("**Document Name:** {$this->document->document_name}");

        if ($this->document->document_number) {
            $message->line("**Document Number:** {$this->document->document_number}");
        }

        $message->line("**Document Type:** {$this->document->document_type}");
        $message->line("**Expiry Date:** {$this->document->expiry_date->format('Y-m-d')}");
        $message->line("**Days Remaining:** {$this->daysRemaining} days");

        // Candidate information if available
        if ($this->document->candidate) {
            $message->line("**Candidate:** {$this->document->candidate->name}");
        }

        // Campus information if available
        if ($this->document->campus) {
            $message->line("**Campus:** {$this->document->campus->name}");
        }

        // Urgency message
        $message->line("")
            ->line($urgencyText);

        // Action button
        $message->action('View Document Details', route('document-archive.show', $this->document));

        // Next steps
        $message->line("**Next Steps:**")
            ->line("1. Review the document and verify expiry date")
            ->line("2. Contact the relevant candidate/department for renewal")
            ->line("3. Upload the renewed document when available")
            ->line("4. Update expiry date in the system");

        return $message;
    }

    /**
     * Get severity level based on days remaining
     */
    protected function getSeverity()
    {
        if ($this->daysRemaining <= 7) {
            return 'CRITICAL';
        } elseif ($this->daysRemaining <= 14) {
            return 'WARNING';
        } else {
            return 'NOTICE';
        }
    }

    /**
     * Get urgency text based on days remaining
     */
    protected function getUrgencyText()
    {
        if ($this->daysRemaining <= 7) {
            return "⚠️ **CRITICAL:** This document expires in {$this->daysRemaining} days. Immediate action required to avoid compliance issues!";
        } elseif ($this->daysRemaining <= 14) {
            return "⚠️ **WARNING:** This document expires in {$this->daysRemaining} days. Please prioritize renewal as soon as possible.";
        } else {
            return "ℹ️ **NOTICE:** This document expires in {$this->daysRemaining} days. Please plan for renewal in the coming weeks.";
        }
    }

    public function toArray($notifiable)
    {
        return [
            'document_id' => $this->document->id,
            'title' => $this->document->title,
            'days_remaining' => $this->daysRemaining,
            'type' => 'document_expiring'
        ];
    }
}
