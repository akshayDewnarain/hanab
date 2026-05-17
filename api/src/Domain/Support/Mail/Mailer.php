<?php

namespace Domain\Support\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\Mailable;

/**
 * Injectable mailer wrapper for DDD-friendly mail sending.
 * Prefer this over direct Mail facade usage in domain/actions.
 */
class Mailer
{
    public function __construct(
        protected MailerContract $mailer
    ) {}

    /**
     * Send a mailable to the given email address.
     */
    public function sendTo(string $email, Mailable $mail): void
    {
        $this->mailer->to($email)->send($mail);
    }

    /**
     * Queue a mailable to the given email address.
     */
    public function queueTo(string $email, Mailable $mail): void
    {
        $this->mailer->to($email)->queue($mail);
    }

    /**
     * Send a mailable to multiple recipients.
     */
    public function sendToMany(array $emails, Mailable $mail): void
    {
        $this->mailer->to($emails)->send($mail);
    }

    /**
     * Queue a mailable to multiple recipients.
     */
    public function queueToMany(array $emails, Mailable $mail): void
    {
        $this->mailer->to($emails)->queue($mail);
    }
}
