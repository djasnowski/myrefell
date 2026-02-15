<?php

namespace App\Providers;

use App\Listeners\CheckReferralQualification;
use App\Listeners\UpdateUserLoginInfo;
use App\Models\Barony;
use App\Models\Duchy;
use App\Models\Kingdom;
use App\Models\Town;
use App\Models\Village;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configureEventListeners();
        $this->configureLogViewer();
    }

    protected function configureLogViewer(): void
    {
        LogViewer::auth(function ($request) {
            return $request->user()?->is_admin === true;
        });
    }

    protected function configureEventListeners(): void
    {
        Event::listen(Login::class, UpdateUserLoginInfo::class);
        Event::listen(Verified::class, CheckReferralQualification::class);
    }

    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'village' => Village::class,
            'town' => Town::class,
            'barony' => Barony::class,
            'duchy' => Duchy::class,
            'kingdom' => Kingdom::class,
        ]);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
