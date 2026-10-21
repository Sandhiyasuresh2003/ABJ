<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class RequestApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $approveUrl;

    public string $rejectUrl;

    public function __construct(
        public LeaveRequest|PermissionRequest $request,
        public string $requestType,
    ) {
        $parameters = ['kind' => $requestType, 'id' => $request->id];
        $expires = now()->addDays(7);
        $this->approveUrl = URL::temporarySignedRoute('requests.review', $expires, $parameters + ['decision' => 'approve']);
        $this->rejectUrl = URL::temporarySignedRoute('requests.review', $expires, $parameters + ['decision' => 'reject']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ucfirst($this->requestType).' request pending approval: '.$this->request->employee->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.requests.approval');
    }
}
