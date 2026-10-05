<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailUz extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        return (new MailMessage)
            ->subject('Email manzilingizni tasdiqlang — UniScience.uz')
            ->greeting('Assalomu alaykum, '.$notifiable->name.'!')
            ->line('UniScience.uz hisobingizdan foydalanishni boshlash uchun email manzilingizni tasdiqlang.')
            ->action('Emailni tasdiqlash', $url)
            ->line('Havola 60 daqiqa davomida amal qiladi.')
            ->salutation('UniScience.uz');
    }
}
