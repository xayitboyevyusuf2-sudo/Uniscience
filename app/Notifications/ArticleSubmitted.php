<?php

namespace App\Notifications;

use App\Models\Article;
use Illuminate\Notifications\Notification;

class ArticleSubmitted extends Notification
{
    public function __construct(public Article $article) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Maqolangiz qabul qilindi: Ko‘rib chiqilmoqda',
            'body' => '«'.$this->article->title.'» maqolangiz tekshiruvga yuborildi.',
            'url' => '/maqola/'.$this->article->id,
            'type' => 'article_submitted',
        ];
    }
}
