<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
{
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('UniScience hisobingiz tasdiqlandi')
            ->greeting('Assalomu alaykum, '.$notifiable->name.'!')
            ->line('Hisobingiz administrator tomonidan tasdiqlandi.')
            ->action('Tizimga kirish', url('/kirish'))
            ->salutation('UniScience.uz');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Hisobingiz tasdiqlandi',
            'body' => 'Administrator hisobingizni tasdiqladi. Endi tizimga kirishingiz mumkin.',
            'url' => '/kirish',
            'type' => 'account_approved',
        ];
    }
}
