<?php

namespace Domain\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $acceptUrl,
        public string $recipientEmail,
        public int $expiresInDays,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.invitation.subject', ['app' => config('app.name', 'Hanab')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.user-invitation',
        );
    }
}
