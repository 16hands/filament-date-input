<?php

namespace SixteenHands\FilamentDateInput;

use Filament\Support\Facades\FilamentAsset;
use SixteenHands\FilamentDateInput\Assets\ContentVersionedAlpineComponent;
use SixteenHands\FilamentDateInput\Assets\ContentVersionedCss;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentDateInputServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-date-input';

    public static string $assetPackage = '16hands/filament-date-input';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            ContentVersionedAlpineComponent::make('date-input', __DIR__.'/../resources/js/date-input.js'),
            ContentVersionedCss::make('date-input', __DIR__.'/../resources/css/date-input.css'),
        ], package: static::$assetPackage);
    }
}
