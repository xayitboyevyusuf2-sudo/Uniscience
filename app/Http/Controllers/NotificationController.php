<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return back()->with('ok', 'Bildirishnoma o‘qildi deb belgilandi.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('ok', 'Barcha bildirishnomalar o‘qildi deb belgilandi.');
    }
}
