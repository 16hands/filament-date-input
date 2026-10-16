<?php

use SixteenHands\FilamentDateInput\DateDisplayFormat;

it('describes d/m/Y the way people write it', function () {
    $displayFormat = DateDisplayFormat::fromFormat('d/m/Y');

    expect($displayFormat->partOrder)->toBe(['d', 'm', 'Y'])
        ->and($displayFormat->separator)->toBe('/')
        ->and($displayFormat->placeholder())->toBe('dd/mm/yyyy')
        ->and($displayFormat->partLengths())->toBe([2, 2, 4])
        ->and($displayFormat->formatIsoDate('1962-03-05'))->toBe('05/03/1962')
        ->and($displayFormat->exampleDate())->toBe('15/03/1962')
        ->and($displayFormat->exampleDigits())->toBe('15031962')
        ->and($displayFormat->exampleSingleDigits())->toBe('1/3/1962')
        ->and($displayFormat->exampleWithOtherSeparator())->toBe('15-03-1962')
        ->and($displayFormat->exampleYear())->toBe('1962')
        ->and($displayFormat->exampleShortYear())->toBe('62');
});

it('follows the order of a year-first format', function () {
    $displayFormat = DateDisplayFormat::fromFormat('Y-m-d');

    expect($displayFormat->placeholder())->toBe('yyyy-mm-dd')
        ->and($displayFormat->partLengths())->toBe([4, 2, 2])
        ->and($displayFormat->formatIsoDate('1962-03-05'))->toBe('1962-03-05')
        ->and($displayFormat->exampleWithOtherSeparator())->toBe('1962.03.15');
});

it('refuses formats people cannot type', function (string $format) {
    expect(fn () => DateDisplayFormat::fromFormat($format))->toThrow(InvalidArgumentException::class);
})->with(['M j, Y', 'd/m/y', 'd/d/Y', 'dmY', 'd/m-Y']);
