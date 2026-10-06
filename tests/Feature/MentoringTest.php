<?php

namespace Tests\Feature;

use App\Models\MentorRequest;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\MentorRequestAnswered;
use App\Notifications\MentorRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MentoringTest extends TestCase
{
    use RefreshDatabase;

    // TS-18: slot shows on mentor profile; student sends a request
    public function test_ts18_slot_on_mentor_profile_and_student_sends_request(): void
    {
        Notification::fake();
        $mentor = $this->createMentor();
        $student = User::factory()->create(['category' => 'bakalavr']);

        $this->actingAs($mentor)->post('/matching/slotlarim', [
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => '205-xona',
        ])->assertRedirect();
        $slot = TimeSlot::firstOrFail();

        $this->actingAs($mentor)->get('/talaba/'.$mentor->id)
            ->assertOk()
            ->assertSee('Bo‘sh vaqt')
            ->assertSee(now()->addDays(2)->format('Y-m-d'))
            ->assertSee('205-xona');

        $this->actingAs($student)->get('/matching')->assertOk()->assertSee('205-xona');
        $this->actingAs($student)->post('/matching/slot/'.$slot->id.'/sorov', ['message' => 'Ilmiy ish mavzusi bo‘yicha maslahat'])
            ->assertRedirect();

        $this->assertDatabaseHas('mentor_requests', [
            'student_id' => $student->id,
            'slot_id' => $slot->id,
            'status' => 'pending',
        ]);
        Notification::assertSentTo($mentor, MentorRequestReceived::class);
    }

    // TS-19: accept → student profile shows "Rejalashtirilgan" + notification
    public function test_ts19_accept_marks_scheduled_and_notifies_student(): void
    {
        Notification::fake();
        $mentor = $this->createMentor();
        $student = User::factory()->create(['category' => 'bakalavr']);
        $slot = $this->createSlot($mentor);
        $request = MentorRequest::create(['student_id' => $student->id, 'slot_id' => $slot->id, 'message' => 'Uchrashuv so‘rovi']);

        $this->actingAs($mentor)->post('/matching/sorovlar/'.$request->id.'/javob', ['decision' => 'accept', 'response_note' => 'Kutaman'])
            ->assertRedirect();

        $this->assertSame('accepted', $request->fresh()->status);
        Notification::assertSentTo($student, MentorRequestAnswered::class);

        $this->actingAs($student)->get('/talaba/'.$student->id)
            ->assertOk()
            ->assertSee('Rejalashtirilgan')
            ->assertSee('Kutaman');
        $this->actingAs($mentor)->get('/talaba/'.$mentor->id)
            ->assertOk()
            ->assertSee('Rejalashtirilgan');
    }

    public function test_at_most_three_active_requests_per_student(): void
    {
        $student = User::factory()->create(['category' => 'bakalavr']);
        $mentor = $this->createMentor();
        foreach (range(1, 3) as $i) {
            $slot = $this->createSlot($mentor, ['slot_date' => now()->addDays($i)->toDateString()]);
            MentorRequest::create(['student_id' => $student->id, 'slot_id' => $slot->id, 'message' => 'So‘rov '.$i]);
        }
        $extra = $this->createSlot($mentor, ['slot_date' => now()->addDays(9)->toDateString()]);

        $this->actingAs($student)->post('/matching/slot/'.$extra->id.'/sorov', ['message' => 'To‘rtinchi'])
            ->assertSessionHasErrors('slot');

        $this->assertSame(3, MentorRequest::where('student_id', $student->id)->whereIn('status', ['pending', 'accepted'])->count());
    }

    public function test_overlapping_slot_and_past_date_are_rejected(): void
    {
        $mentor = $this->createMentor();
        $this->createSlot($mentor, ['slot_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00']);

        $this->actingAs($mentor)->post('/matching/slotlarim', [
            'slot_date' => now()->addDay()->toDateString(),
            'start_time' => '10:30',
            'end_time' => '11:30',
            'location' => 'Boshqa xona',
        ])->assertSessionHasErrors('start_time');

        $this->actingAs($mentor)->post('/matching/slotlarim', [
            'slot_date' => now()->subDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'location' => 'Onlayn',
        ])->assertSessionHasErrors('slot_date');

        $this->actingAs($mentor)->post('/matching/slotlarim', [
            'slot_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '12:00',
            'location' => 'Onlayn',
        ])->assertSessionHasErrors('end_time');

        $this->assertSame(1, TimeSlot::count());
    }

    public function test_own_slot_request_and_duplicate_request_are_rejected(): void
    {
        $mentor = $this->createMentor();
        $tadqiqotchi = User::factory()->create(['category' => 'tadqiqotchi']);
        $slot = $this->createSlot($tadqiqotchi);
        $student = User::factory()->create(['category' => 'bakalavr']);

        $this->actingAs($tadqiqotchi)->post('/matching/slot/'.$slot->id.'/sorov', ['message' => 'O‘z slotim'])
            ->assertSessionHasErrors('slot');

        $this->actingAs($student)->post('/matching/slot/'.$slot->id.'/sorov', ['message' => 'Birinchi so‘rov'])->assertRedirect();
        $this->actingAs($student)->post('/matching/slot/'.$slot->id.'/sorov', ['message' => 'Takroriy so‘rov'])
            ->assertSessionHasErrors('slot');

        $this->assertSame(1, MentorRequest::where('student_id', $student->id)->count());
    }

    public function test_only_slot_owner_answers_and_only_request_owner_cancels(): void
    {
        Notification::fake();
        $mentor = $this->createMentor();
        $otherMentor = User::factory()->create(['category' => 'professor', 'approval_status' => 'approved']);
        $student = User::factory()->create(['category' => 'bakalavr']);
        $otherStudent = User::factory()->create(['category' => 'bakalavr']);
        $slot = $this->createSlot($mentor);
        $request = MentorRequest::create(['student_id' => $student->id, 'slot_id' => $slot->id, 'message' => 'So‘rov']);

        $this->actingAs($otherMentor)->post('/matching/sorovlar/'.$request->id.'/javob', ['decision' => 'accept'])->assertForbidden();
        $this->actingAs($student)->post('/matching/sorovlar/'.$request->id.'/javob', ['decision' => 'accept'])->assertForbidden();

        $this->actingAs($otherStudent)->post('/matching/sorov/'.$request->id.'/bekor')->assertForbidden();

        $this->actingAs($student)->post('/matching/sorov/'.$request->id.'/bekor')->assertRedirect();
        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_deleting_slot_cancels_active_requests_and_notifies(): void
    {
        Notification::fake();
        $mentor = $this->createMentor();
        $student = User::factory()->create(['category' => 'bakalavr']);
        $slot = $this->createSlot($mentor);
        $request = MentorRequest::create(['student_id' => $student->id, 'slot_id' => $slot->id, 'message' => 'So‘rov']);

        $this->actingAs($mentor)->delete('/matching/slotlarim/'.$slot->id)->assertRedirect();

        // cascade removes the row, but the cancellation ran first and the student was notified
        Notification::assertSentTo($student, MentorRequestAnswered::class, fn ($notification) => $notification->mentorRequest->status === 'cancelled');
        $this->assertDatabaseMissing('time_slots', ['id' => $slot->id]);
    }

    public function test_non_mentors_cannot_create_slots_and_pending_mentors_are_blocked(): void
    {
        $student = User::factory()->create(['category' => 'bakalavr']);
        $pendingMentor = User::factory()->create(['category' => 'professor', 'approval_status' => 'pending']);

        $this->actingAs($student)->post('/matching/slotlarim', [
            'slot_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => 'Onlayn',
        ])->assertForbidden();
        $this->actingAs($pendingMentor)->post('/matching/slotlarim', [
            'slot_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => 'Onlayn',
        ])->assertForbidden();
    }

    private function createMentor(): User
    {
        return User::factory()->create([
            'category' => 'professor',
            'approval_status' => 'approved',
            'faculty' => 'Iqtisodiyot',
        ]);
    }

    private function createSlot(User $mentor, array $overrides = []): TimeSlot
    {
        return TimeSlot::create(array_merge([
            'user_id' => $mentor->id,
            'slot_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'location' => 'Onlayn',
        ], $overrides));
    }
}
