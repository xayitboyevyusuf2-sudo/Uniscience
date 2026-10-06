<?php

namespace App\Notifications;

use App\Models\MentorRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Student gets told when the mentor answers their request (accepted/rejected/cancelled). */
class MentorRequestAnswered extends Notification
{
    public function __construct(public MentorRequest $mentorRequest) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $request = $this->mentorRequest->loadMissing(['slot.mentor']);
        $labels = ['accepted' => 'qabul qilindi', 'rejected' => 'rad etildi', 'cancelled' => 'bekor qilindi'];
        $slot = $request->slot;

        return (new MailMessage)->subject('Mentorlik so‘rovingiz: '.$labels[$request->status])
            ->greeting('Assalomu alaykum, '.$notifiable->name.'!')
            ->line($slot->mentor->name.' — '.$slot->slot_date->format('Y-m-d').' '.substr($slot->start_time, 0, 5).' slotiga yuborgan so‘rovingiz '.$labels[$request->status].'.')
            ->when($request->response_note, fn ($m) => $m->line('Izoh: '.$request->response_note))
            ->action('Profilni ko‘rish', url('/talaba/'.$notifiable->id))
            ->salutation('UniScience.uz');
    }

    public function toArray($notifiable): array
    {
        $request = $this->mentorRequest->loadMissing(['slot.mentor']);
        $labels = ['accepted' => 'qabul qilindi', 'rejected' => 'rad etildi', 'cancelled' => 'bekor qilindi'];
        $slot = $request->slot;

        return [
            'title' => 'Mentorlik so‘rovingiz: '.$labels[$request->status],
            'body' => $slot->mentor->name.' — '.$slot->slot_date->format('Y-m-d').' '.substr($slot->start_time, 0, 5).' slotiga yuborgan so‘rovingiz '.$labels[$request->status].'.',
            'url' => '/talaba/'.$notifiable->id,
            'type' => 'mentor_request_answered',
        ];
    }
}
