<?php

namespace Domain\Support\Mail;

use Domain\General\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class TemplateMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $locationId
    ) {}

    /**
     * Build the message.
     */
    public function build(): self
    {
        $this->setDefaults();

        return $this->view($this->getViewName())
            ->with($this->getSharedViewData());
    }

    /**
     * Set subject with automatic prefix.
     */
    public function withSubjectPrefix(string $subject): self
    {
        return $this->subject($this->subjectPrefix($subject));
    }

    /**
     * Set default from and reply-to addresses from config.
     */
    protected function setDefaults(): void
    {
        $fromAddress = config('mail.from.address');
        $fromName = $this->getLocationName();

        if ($fromAddress) {
            $this->from($fromAddress, $fromName);
        }

        // Set reply-to to support email if configured
        $replyTo = config('mail.reply_to.address', $fromAddress);
        if ($replyTo) {
            $this->replyTo($replyTo, config('mail.reply_to.name', $fromName));
        }
    }

    /**
     * Get the view name for this mail.
     * Override in child classes or set $view property.
     */
    protected function getViewName(): string
    {
        return $this->view ?? 'mail.layouts.base';
    }

    /**
     * Get shared view data available to all mail templates.
     */
    protected function getSharedViewData(): array
    {
        return [
            'appName' => $this->getLocationName(),
            'appUrl' => config('app.url', 'http://localhost'),
            'supportEmail' => config('mail.from.address', 'hello@example.com'),
            'logoUrl' => $this->getLogoUrl(),
        ];
    }

    protected function getLocationName(): string
    {
        $region = Setting::where('location_id', $this->locationId)
            ->where('key', 'region')
            ->pluck('value')
            ->first();

        return $region ? 'BBQ-'.$region : 'BBQ-Betuwe';
    }

    /**
     * Get the logo URL for emails.
     * Override in child classes or set via config.
     */
    protected function getLogoUrl(): ?string
    {
        $logoUrl = config('mail.logo_url');

        if ($logoUrl) {
            return $logoUrl;
        }

        // Fallback to public logo if exists
        $appUrl = config('app.url', 'http://localhost');

        return mb_rtrim($appUrl, '/').'/betuwe_logo.png';
    }

    /**
     * Add a prefix to the subject line.
     */
    protected function subjectPrefix(string $prefix): string
    {
        $appName = config('app.name', 'Laravel');

        return "[{$appName}] {$prefix}";
    }

    /**
     * Add branding data to view.
     */
    protected function withBranding(array $data = []): array
    {
        return array_merge($this->getSharedViewData(), $data);
    }
}
