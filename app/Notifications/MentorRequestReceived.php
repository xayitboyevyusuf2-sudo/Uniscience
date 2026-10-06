<?php

namespace App\Notifications;

use App\Models\MentorRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Mentor gets told when a student sends a request for one of their slots. */
class MentorRequestReceived extends Notification
{
    public function __construct(public MentorRequest $mentorRequest) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $request = $this->mentorRequest->loadMissing(['student', 'slot']);

        return (new MailMessage)->subject('Yangi mentorlik so‘rovi')
            ->greeting('Assalomu alaykum, '.$notifiable->name.'!')
            ->line($request->student->name.' sizning '.$request->slot->slot_date->format('Y-m-d').' '.substr($request->slot->start_time, 0, 5).' slotingizga so‘rov yubordi.')
            ->line('Xabar: '.$request->message)
            ->action('So‘rovlarni ko‘rish', url('/matching/sorovlar'))
            ->salutation('UniScience.uz');
    }

    public function toArray($notifiable): array
    {
        $request = $this->mentorRequest->loadMissing(['student', 'slot']);

        return [
            'title' => 'Yangi mentorlik so‘rovi',
            'body' => $request->student->name.' — '.$request->slot->slot_date->format('Y-m-d').' '.substr($request->slot->start_time, 0, 5).' slotiga so‘rov.',
            'url' => '/matching/sorovlar',
            'type' => 'mentor_request_received',
        ];
    }
}
