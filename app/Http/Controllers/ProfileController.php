<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminEditUserRequest;
use App\Models\MentorRequest;
use App\Models\User;
use App\Services\AuditLog;
use App\Services\RatingService;
use App\Services\Scorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user): View // logged-in users only (see routes)
    {
        $s = Scorer::for($user);
        $rating = $user->rating ?? RatingService::recalculateUser($user);
        if ($rating->rank_university === null) {
            RatingService::refreshRanksFor($rating->category);
            $rating->refresh();
        }

        return view('profile.show', ['u' => $user, 's' => $s, 'rows' => collect($s['rows'])->values(), 'rating' => $rating] + $this->mentoringData($user));
    }

    /** @return array<string, mixed> */
    private function mentoringData(User $user): array
    {
        $viewer = auth()->user();
        $data = ['sentRequests' => collect(), 'scheduledMeetings' => collect(), 'mentorSlots' => collect(), 'incomingRequests' => collect()];

        if (in_array($user->category, ['bakalavr', 'magistr', 'tadqiqotchi'], true)) {
            $data['sentRequests'] = $user->mentorRequests()->with('slot.mentor')->latest()->get();
        }
        if ($user->isMentor()) {
            $data['mentorSlots'] = $user->slots()->withCount('requests')->whereDate('slot_date', '>=', now()->toDateString())->orderBy('slot_date')->orderBy('start_time')->get();
            if ($viewer->id === $user->id) {
                $data['incomingRequests'] = MentorRequest::query()->whereHas('slot', fn ($q) => $q->where('user_id', $user->id))
                    ->with(['student.rating', 'slot'])->where('status', 'pending')->oldest()->get();
            }
        }
        $scheduledQuery = MentorRequest::query()->where('status', 'accepted')
            ->whereHas('slot', fn ($q) => $q->whereDate('slot_date', '>=', now()->toDateString()))
            ->with(['slot.mentor', 'student']);
        if ($user->isMentor()) {
            $scheduledQuery->whereHas('slot', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $scheduledQuery->where('student_id', $user->id);
        }
        $data['scheduledMeetings'] = $scheduledQuery->oldest('responded_at')->get();

        return $data;
    }

    public function edit(): View
    {
        return view('profile.edit', ['u' => auth()->user()]);
    }

    public function update(Request $r): RedirectResponse
    {
        $u = $r->user();
        $isStudent = $u->isStudent();
        $d = $r->validate([
            'course' => [Rule::excludeIf(! $isStudent), Rule::requiredIf($isStudent), 'integer', 'between:1,6'],
            'group_name' => [Rule::excludeIf(! $isStudent), 'nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:1000'], 'interests' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:20'], 'telegram' => ['nullable', 'string', 'max:60'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
            'remove_photo' => ['sometimes', 'accepted'],
            'current_password' => ['required_with:password', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required_with' => 'Parolni o‘zgartirish uchun joriy parolni kiriting.',
            'current_password.current_password' => 'Joriy parol noto‘g‘ri.',
            'password.confirmed' => 'Yangi parollar mos kelmadi.',
            'password.min' => 'Yangi parol kamida 8 belgidan iborat bo‘lishi kerak.',
            'password.letters' => 'Yangi parolda kamida bitta harf bo‘lishi kerak.',
            'password.numbers' => 'Yangi parolda kamida bitta raqam bo‘lishi kerak.',
            'photo.image' => 'Rasm fayli noto‘g‘ri.',
            'photo.mimes' => 'Rasm faqat JPG, JPEG yoki PNG bo‘lishi mumkin.',
            'photo.extensions' => 'Rasm faqat JPG, JPEG yoki PNG bo‘lishi mumkin.',
            'photo.max' => 'Rasm hajmi 5 MB dan oshmasligi kerak.',
        ]);

        $attributes = Arr::only($d, ['course', 'group_name', 'bio', 'interests', 'phone', 'telegram']);
        if (filled($d['password'] ?? null)) {
            $attributes['password'] = $d['password'];
        }
        $oldPhotoPath = $u->photo_path;
        $newPhotoPath = $oldPhotoPath;
        if ($r->hasFile('photo')) {
            $newPhotoPath = $this->storeProfilePhoto($r->file('photo'));
            $attributes['photo_path'] = $newPhotoPath;
        } elseif ($r->boolean('remove_photo')) {
            $newPhotoPath = null;
            $attributes['photo_path'] = null;
        }

        $u->update($attributes);
        if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            Storage::disk('local')->delete($oldPhotoPath);
        }

        return redirect('/talaba/'.$u->id)->with('ok', 'Profil saqlandi');
    }

    public function photo(User $user)
    {
        abort_unless($user->photo_path && Storage::disk('local')->exists($user->photo_path), 404);

        return Storage::disk('local')->response($user->photo_path);
    }

    private function storeProfilePhoto(UploadedFile $photo): string
    {
        $disk = Storage::disk('local');
        $mime = $photo->getMimeType();
        $extension = $mime === 'image/png' ? 'png' : ($mime === 'image/jpeg' ? 'jpg' : null);
        $hashName = $photo->hashName('photos');
        $path = 'photos/'.pathinfo($hashName, PATHINFO_FILENAME).'.'.($extension ?? pathinfo($hashName, PATHINFO_EXTENSION));

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecopyresampled') || ! function_exists('imagepng') || ! function_exists('imagejpeg')) {
            Log::warning('GD extension is unavailable; storing the original profile photo.', ['path' => $path]);
            $photo->storeAs('photos', basename($path), 'local');

            return $path;
        }

        $bytes = file_get_contents($photo->getRealPath());
        $source = $bytes === false ? false : @imagecreatefromstring($bytes);
        if ($source === false) {
            Log::warning('Profile photo could not be decoded by GD; storing the original file.', ['path' => $path]);
            $photo->storeAs('photos', basename($path), 'local');

            return $path;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $offsetX = intdiv($width - $side, 2);
        $offsetY = intdiv($height - $side, 2);
        $cropped = imagecreatetruecolor(512, 512);

        if ($mime === 'image/png') {
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
        }

        imagecopyresampled($cropped, $source, 0, 0, $offsetX, $offsetY, 512, 512, $side, $side);
        ob_start();
        if ($mime === 'image/png') {
            imagepng($cropped);
        } else {
            imagejpeg($cropped, null, 90);
        }
        $output = ob_get_clean();
        imagedestroy($source);
        imagedestroy($cropped);
        $disk->put($path, $output);

        return $path;
    }

    public function editUser(User $user): View
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        return view('profile.admin-edit', ['user' => $user]);
    }

    public function updateUser(AdminEditUserRequest $r, User $user): RedirectResponse
    {
        $d = $r->validated();
        $old = $user->only(['first_name', 'last_name', 'patronymic', 'name', 'username', 'university', 'faculty', 'direction', 'group_name']);
        $new = $d + [
            'patronymic' => $d['patronymic'] ?? null,
            'group_name' => $d['group_name'] ?? null,
            'name' => trim($d['first_name'].' '.$d['last_name']),
            'username' => trim($d['last_name'].' '.$d['first_name'].' '.($d['patronymic'] ?? '')),
        ];
        $user->update($new);
        AuditLog::record('user.profile_updated', $user, $old, $user->only(['first_name', 'last_name', 'patronymic', 'name', 'username', 'university', 'faculty', 'direction', 'group_name']));
        RatingService::refreshAfterChange($user->fresh());

        return redirect('/talaba/'.$user->id)->with('ok', 'Foydalanuvchi profili yangilandi.');
    }
}
