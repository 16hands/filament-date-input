<?php

use Illuminate\Support\Carbon;
use Livewire\Livewire;
use SixteenHands\FilamentDateInput\DateInput;
use SixteenHands\FilamentDateInput\DateInputView;
use SixteenHands\FilamentDateInput\Tests\Fixtures\DateInputTestForm;

beforeEach(function () {
    DateInputTestForm::$configureDateInput = null;
    Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00', 'Pacific/Auckland'));
});

afterEach(function () {
    DateInputTestForm::$configureDateInput = null;
    Carbon::setTestNow();
});

it('puts a (?) beside the label that opens the help', function () {
    Livewire::test(DateInputTestForm::class)
        ->assertSeeHtml('fi-date-input-help-trigger')
        ->assertSeeHtml('data-date-input-help-for="form.date"')
        ->assertSee('How to enter dates for Date of Birth')
        ->assertSee('Entering dates')
        ->assertSee('15031962')
        ->assertSee('1/3/1962')
        ->assertSee('15-03-1962')
        ->assertSee('Always the full year: 1962, not 62')
        ->assertSee('Calendar: decade → year → month → day');
});

it('keeps the (?) out of the Tab order', function () {
    $renderedHtml = Livewire::test(DateInputTestForm::class)->html();

    expect($renderedHtml)->toMatch('/<button(?=[^>]*tabindex="-1")(?=[^>]*fi-date-input-help-trigger)[^>]*>/');
});

it('leaves the help out when it is switched off', function () {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput->helpPopover(false);

    Livewire::test(DateInputTestForm::class)
        ->assertDontSeeHtml('fi-date-input-help-trigger')
        ->assertDontSeeHtml('data-date-input-help-for')
        ->assertDontSee('Press ? for help')
        ->assertDontSee('How to enter dates');
});

it('ends with what is special about this field', function (Closure $configureDateInput, ?string $expectedFieldNote) {
    DateInputTestForm::$configureDateInput = $configureDateInput;

    Livewire::test(DateInputTestForm::class)
        ->assertFormFieldExists('date', fn (DateInput $dateInput): bool => $dateInput->getHelpFieldNote() === $expectedFieldNote);
})->with([
    'a date of birth' => [
        fn (DateInput $dateInput): DateInput => $dateInput->pastDate(),
        "This field: can't be in the future; the calendar opens at decades.",
    ],
    'a bounded range' => [
        fn (DateInput $dateInput): DateInput => $dateInput->minDate('2020-01-01')->maxDate('2020-12-31'),
        'This field: on or before 31/12/2020; on or after 01/01/2020.',
    ],
    'an ordinary date' => [
        fn (DateInput $dateInput): DateInput => $dateInput,
        null,
    ],
]);

it('only lists the calendar levels the field has', function () {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput->views([DateInputView::Day]);

    Livewire::test(DateInputTestForm::class)
        ->assertSee('Calendar: day')
        ->assertDontSee('Click the heading to go back up a level');
});
