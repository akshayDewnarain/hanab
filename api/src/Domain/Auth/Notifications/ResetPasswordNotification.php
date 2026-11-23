<?php

namespace Domain\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $app = config('app.name', 'Hanab');
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject(__('mail.reset.subject', ['app' => $app]))
            ->line(__('mail.reset.intro'))
            ->action(__('mail.reset.action'), $url)
            ->line(__('mail.reset.expire', ['minutes' => $minutes]))
            ->line(__('mail.reset.fallback'))
            ->line($url)
            ->line(__('mail.reset.ignore'));
    }

    protected function resetUrl($notifiable): string
    {
        $base = rtrim((string) config('invitation.frontend_url'), '/');
        $path = trim((string) config('invitation.reset_path', '/reset-password'), '/');
        $email = urlencode((string) $notifiable->getEmailForPasswordReset());

        return "{$base}/{$path}?token={$this->token}&email={$email}";
    }
}
