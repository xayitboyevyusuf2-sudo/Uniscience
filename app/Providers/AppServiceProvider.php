<?php

namespace App\Providers;

use App\Integrations\Hemis\HemisAuthAdapter;
use App\Integrations\Hemis\NullHemisAdapter;
use App\Integrations\Oak\NullOakSyncAdapter;
use App\Integrations\Oak\OakSyncAdapter;
use App\Services\Verification\RuleBasedVerifier;
use App\Services\Verification\VerificationAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        $this->app->bind(OakSyncAdapter::class, NullOakSyncAdapter::class);
        $this->app->bind(VerificationAdapter::class, RuleBasedVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->environment('local') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

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
