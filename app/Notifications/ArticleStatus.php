<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** FR-07 / FR-14: tells the student what happened to the article. Sent synchronously (no queue worker needed). */
class ArticleStatus extends Notification
{
    public function __construct(public Article $article) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $a = $this->article;
        $t = ['approved' => 'Tasdiqlandi', 'rejected' => 'Rad etildi', 'manual' => 'Qo‘lda tekshiruvda', 'pending' => 'Ko‘rib chiqilmoqda'][$a->status] ?? $a->status;

        return (new MailMessage)->subject('Maqolangiz holati: '.$t)
            ->greeting('Assalomu alaykum, '.$notifiable->name.'!')
            ->line('«'.$a->title.'» maqolangiz holati: '.$t.'.')
            ->when($a->reason, fn ($m) => $m->line($a->reason))
            ->action('Maqolani ko‘rish', url('/maqola/'.$a->id))
            ->salutation('UniScience.uz');
    }

    public function toArray($notifiable): array
    {
        $a = $this->article;
        $status = ['approved' => 'Tasdiqlandi', 'rejected' => 'Rad etildi', 'manual' => 'Qo‘lda tekshiruvda', 'pending' => 'Ko‘rib chiqilmoqda'][$a->status] ?? $a->status;

        return [
            'title' => 'Maqolangiz holati: '.$status,
            'body' => '«'.$a->title.'» maqolangiz holati: '.$status.'.'.($a->reason ? ' '.$a->reason : ''),
            'url' => '/maqola/'.$a->id,
            'type' => 'article_status',
        ];
    }
}
