<?php

use Filament\Support\Facades\FilamentAsset;

it('tags its stylesheet and script with a hash of their contents, so browsers refetch after a change', function () {
    $stylesheetHash = substr(hash_file('sha256', dirname(__DIR__, 2).'/resources/css/date-input.css'), 0, 16);
    $scriptHash = substr(hash_file('sha256', dirname(__DIR__, 2).'/resources/js/date-input.js'), 0, 16);

    expect(FilamentAsset::getStyleHref('date-input', '16hands/filament-date-input'))->toEndWith("date-input.css?v={$stylesheetHash}")
        ->and(FilamentAsset::getAlpineComponentSrc('date-input', '16hands/filament-date-input'))->toEndWith("date-input.js?v={$scriptHash}");
});
