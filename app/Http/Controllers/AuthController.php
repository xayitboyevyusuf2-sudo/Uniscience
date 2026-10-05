<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        if ($isMentor) {
            return redirect()->route('registration.pending');
        }
        Auth::login($u);
        $r->session()->regenerate();

        return redirect('/portfel');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required'], [
            'email.required' => 'Email manzilini kiriting.', 'email.email' => 'Email manzili noto‘g‘ri.', 'password.required' => 'Parolni kiriting.',
        ]);
        $u = User::where('email', $c['email'])->first();
        if ($u && Hash::check($c['password'], $u->password)) {
            if ($u->approval_status === 'pending') {
                return back()->withErrors(['email' => 'Admin tasdig‘i kutilmoqda. Tasdiqlangach, tizimga kirishingiz mumkin.']);
            }
            if ($u->approval_status === 'rejected') {
                return back()->withErrors(['email' => 'Arizangiz rad etilgan. Batafsil ma’lumot uchun administratorga murojaat qiling.']);
            }
        }
        if (! Auth::attempt($c + ['blocked' => false, 'approval_status' => 'approved'], $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email yoki parol noto‘g‘ri']);
        }
        $r->session()->regenerate();

        return redirect()->intended('/portfel');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }
}
