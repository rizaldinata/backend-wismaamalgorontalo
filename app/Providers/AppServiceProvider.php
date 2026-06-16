<?php

namespace App\Providers;

use App\Contracts\ActiveTenantCheckerInterface;
use App\Contracts\ConfigProviderInterface;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\Setting\Services\SettingService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConfigProviderInterface::class, SettingService::class);
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

        // Gate khusus penghuni aktif — depend ke contract, implementasi di Schedule Core
        Gate::define('resident-access', function ($user) {
            return app(ActiveTenantCheckerInterface::class)->isActiveTenant($user->id);
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
