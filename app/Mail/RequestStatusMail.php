<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LeaveRequest|PermissionRequest $request,
        public string $requestType,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->requestType === 'leave' ? 'Leave' : 'Permission';

        return new Envelope(subject: "{$label} request {$this->request->status}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.requests.status',
            with: [
                'request' => $this->request,
                'requestType' => $this->requestType,
            ],
        );
    }
}
