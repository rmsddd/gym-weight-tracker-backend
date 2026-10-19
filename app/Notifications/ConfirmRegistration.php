<?php

namespace App\Notifications;

use App\Models\PendingRegistration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmRegistration extends Notification
{
    public const LINK_MINUTES = 60;

    public function __construct(
        public PendingRegistration $registration
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** The link stays on the API (verification.verify), which creates the account and redirects to the Vue app. */
    public function url(): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(self::LINK_MINUTES), [
            'id' => $this->registration->id,
            'hash' => sha1($this->registration->email),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your email')
            ->greeting("Hi {$this->registration->name}!")
            ->line('Tap the button below to confirm this is your email address and finish creating your account.')
            ->action('Confirm email', $this->url())
            ->line('The link works for '.self::LINK_MINUTES.' minutes. If you did not sign up, you can ignore this email: no account is created.');
    }
}
