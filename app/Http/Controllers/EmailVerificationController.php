<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect('/profil')->with('ok', 'Email manzilingiz tasdiqlandi.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('ok', 'Email manzilingiz allaqachon tasdiqlangan.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('ok', 'Tasdiqlash havolasi email manzilingizga yuborildi.');
    }
}
