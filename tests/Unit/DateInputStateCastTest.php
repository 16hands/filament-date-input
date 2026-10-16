<?php

use Illuminate\Support\Carbon;
use SixteenHands\FilamentDateInput\DateDisplayFormat;
use SixteenHands\FilamentDateInput\DateInput;
use SixteenHands\FilamentDateInput\DateInputStateCast;

function dayMonthYearStateCast(): DateInputStateCast
{
    return new DateInputStateCast(DateDisplayFormat::fromFormat('d/m/Y'));
}

it('loads stored values into the box as the Auckland calendar date', function (mixed $storedValue, ?string $expectedState) {
    expect(dayMonthYearStateCast()->set($storedValue))->toBe($expectedState);
})->with([
    'plain date' => ['2024-08-13', '2024-08-13'],
    'datetime column' => ['2024-08-13 00:00:00', '2024-08-13'],
    'date cast serialised to UTC (midnight in Auckland)' => ['2024-08-12T12:00:00.000000Z', '2024-08-13'],
    'Carbon in Auckland' => [Carbon::parse('2024-08-13', 'Pacific/Auckland'), '2024-08-13'],
    'MySQL zero date' => ['0000-00-00', null],
    'MySQL zero datetime' => ['0000-00-00 00:00:00', null],
    'Carbon made from a zero date' => [Carbon::create(-1, 11, 30, 0, 0, 0, 'UTC'), null],
    'empty string' => ['', null],
    'null' => [null, null],
    'legacy text that is not a date' => ['not known', 'not known'],
]);

it('saves Y-m-d whichever way a valid date reached the server', function (mixed $livewireState, ?string $expectedSavedValue) {
    expect(dayMonthYearStateCast()->get($livewireState))->toBe($expectedSavedValue);
})->with([
    'already Y-m-d' => ['1962-03-15', '1962-03-15'],
    'as displayed' => ['15/03/1962', '1962-03-15'],
    'empty' => ['', null],
    'null' => [null, null],
    'rejected text is left for validation' => ['15/3/62', '15/3/62'],
]);

it('reads a date out of Livewire state only when it is a real Y-m-d', function (mixed $livewireState, ?string $expectedIsoDate) {
    expect(DateInput::dateFromState($livewireState)?->toDateString())->toBe($expectedIsoDate);
})->with([
    'valid' => ['1962-03-15', '1962-03-15'],
    'leap day' => ['1964-02-29', '1964-02-29'],
    'no such day' => ['1962-02-29', null],
    'display format' => ['15/03/1962', null],
    'rejected text' => ['15/3/62', null],
    'null' => [null, null],
    'not a string' => [19620315, null],
]);
