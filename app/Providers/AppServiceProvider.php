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
        }

        Gate::before(function ($user, $ability) {
            return $user->hasROle('super-admin') ? true : null;
        });

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
    }
}
