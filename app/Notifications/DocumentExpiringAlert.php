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
        return (new MailMessage)
            ->subject('Document Expiry Alert - ' . $this->document->title)
            ->line('Document "' . $this->document->title . '" is expiring soon')
            ->line('Days remaining: ' . $this->daysRemaining)
            ->action('View Document', route('document-archive.show', $this->document))
            ->line('Please renew or replace this document');
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
