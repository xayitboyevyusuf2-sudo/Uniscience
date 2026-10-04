<?php
namespace App\Notifications;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class ResetPasswordUz extends Notification {
 public function __construct(public string $token) {}
 public function via($notifiable): array { return ['mail']; }
 public function toMail($notifiable): MailMessage {
  $url = url('/parolni-tiklash/'.$this->token).'?email='.urlencode($notifiable->email);
  return (new MailMessage)->subject('Parolni tiklash — UniScience.uz')
   ->greeting('Assalomu alaykum!')
   ->line('Hisobingiz uchun parolni tiklash so‘rovi keldi.')
   ->action('Parolni tiklash', $url)
   ->line('Havola '.config('auth.passwords.users.expire').' daqiqa amal qiladi.')
   ->line('Agar bu so‘rovni siz yubormagan bo‘lsangiz, hech narsa qilmang.')
   ->salutation('UniScience.uz');
 }
}
