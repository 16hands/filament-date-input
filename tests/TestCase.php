<?php

namespace SixteenHands\FilamentDateInput\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Encryption\Encrypter;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use SixteenHands\FilamentDateInput\FilamentDateInputServiceProvider;

class TestCase extends Orchestra
{
    /**
     * The tests were written for an app in Pacific/Auckland: a `date` cast
     * reaches Livewire as UTC (2024-08-12T12:00Z is 13 August there) and
     * "today" is Auckland's. In UTC the same assertions would name other days.
     */
    public const string APP_TIMEZONE = 'Pacific/Auckland';

    protected function getPackageProviders($app)
    {
        // `filament/forms` brings these Filament packages with it today; skip any
        // that a future release splits out, so the suite still boots.
        return array_values(array_filter([
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            FilamentDateInputServiceProvider::class,
        ], fn (string $provider): bool => class_exists($provider)));
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');

        // Livewire signs component snapshots with the app key, so rendering a form needs one.
        $appKey = 'base64:'.base64_encode(Encrypter::generateKey($app['config']->get('app.cipher')));
        $app['config']->set('app.key', $appKey);

        $app['config']->set('app.timezone', self::APP_TIMEZONE);
        date_default_timezone_set(self::APP_TIMEZONE);
    }
}
