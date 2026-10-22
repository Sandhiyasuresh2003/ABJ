<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSetupNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $setupUrl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Set up your ABJ Builders account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your ABJ Builders account has been created.')
            ->action('Set your password', $this->setupUrl)
            ->line('This link expires in 60 minutes.');
    }
}
