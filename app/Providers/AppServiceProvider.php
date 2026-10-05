<?php

namespace App\Providers;

use App\Integrations\Hemis\HemisAuthAdapter;
use App\Integrations\Hemis\NullHemisAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HemisAuthAdapter::class, NullHemisAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();

            if (! $user) {
                $view->with('notificationUnreadCount', 0)
                    ->with('recentNotifications', collect());

                return;
            }

            $view->with('notificationUnreadCount', $user->unreadNotifications()->count())
                ->with('recentNotifications', $user->notifications()->latest()->limit(5)->get());
        });
    }
}
