<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production') || $this->app->environment('staging')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole')) {
                return $user->hasRole('super-admin') ? true : null;
            }
            return null;
        });

        // Mengizinkan akses publik ke dokumentasi API
        Gate::define('viewApiDocs', function ($user = null) {
            return true;
        });

        // Gate khusus penghuni aktif
        Gate::define('resident-access', function ($user) {
            return $user->hasActiveLease();
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
