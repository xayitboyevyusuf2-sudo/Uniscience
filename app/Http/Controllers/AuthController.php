<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $r)
    {
        $d = $r->validated();
        $isMentor = in_array($d['category'], ['tadqiqotchi', 'professor'], true);
        $u = User::create(Arr::except($d, ['consent', 'password_confirmation']) + [
            'name' => trim($d['first_name'].' '.$d['last_name']),
            'username' => trim($d['last_name'].' '.$d['first_name'].' '.$d['patronymic']),
            'role' => 'student', 'approval_status' => $isMentor ? 'pending' : 'approved', 'consent_at' => now(),
        ]);
        $u->sendEmailVerificationNotification();

        if ($isMentor) {
            return redirect()->route('registration.pending');
        }
        Auth::login($u);
        $r->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $credentials = $r->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email yoki username kiriting.',
            'login.max' => 'Email yoki username juda uzun.',
            'password.required' => 'Parolni kiriting.',
        ]);

        $normalizedLogin = Str::lower(trim($credentials['login']));
        $limiterKey = 'login:'.$normalizedLogin.'|'.$r->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return $this->loginLockedResponse($limiterKey);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedLogin])
            ->orWhereRaw('LOWER(username) = ?', [$normalizedLogin])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->failedLoginResponse($limiterKey);
        }

        if ($user->approval_status === 'pending') {
            return back()->withErrors(['login' => 'Admin tasdig‘i kutilmoqda. Tasdiqlangach, tizimga kirishingiz mumkin.']);
        }

        if ($user->approval_status === 'rejected') {
            return back()->withErrors(['login' => 'Arizangiz rad etilgan. Batafsil ma’lumot uchun administratorga murojaat qiling.']);
        }

        if ($user->blocked) {
            return back()->withErrors(['login' => 'Hisobingiz bloklangan. Administratorga murojaat qiling.']);
        }

        Auth::guard('web')->setRememberDuration(43200);

        if (! Auth::attempt([
            'id' => $user->getAuthIdentifier(),
            'password' => $credentials['password'],
            'approval_status' => 'approved',
            'blocked' => false,
        ], $r->boolean('remember'))) {
            return $this->failedLoginResponse($limiterKey);
        }

        RateLimiter::clear($limiterKey);
        $r->session()->regenerate();
        $this->limitDatabaseSessions($user);

        return redirect()->intended('/portfel');
    }

    private function failedLoginResponse(string $limiterKey): RedirectResponse
    {
        RateLimiter::hit($limiterKey, 900);

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return $this->loginLockedResponse($limiterKey);
        }

        return back()->withErrors(['login' => 'Login yoki parol noto‘g‘ri.']);
    }

    private function loginLockedResponse(string $limiterKey): RedirectResponse
    {
        $minutes = max(1, (int) ceil(RateLimiter::availableIn($limiterKey) / 60));

        return back()->withErrors([
            'login' => "Juda ko‘p noto‘g‘ri urinish bo‘ldi. {$minutes} daqiqadan so‘ng urinib ko‘ring.",
        ]);
    }

    private function limitDatabaseSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return;
        }

        $excessSessionIds = DB::table($table)
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->orderByDesc('id')
            ->get(['id'])
            ->skip(2)
            ->pluck('id');

        if ($excessSessionIds->isNotEmpty()) {
            DB::table($table)->whereIn('id', $excessSessionIds)->delete();
        }
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }
}
