<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
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

it('stores a valid date as Y-m-d', function (string $typedText) {
    Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => $typedText])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSet('savedState.date', '1962-03-15');
})->with(['as committed by the browser' => '1962-03-15', 'as displayed' => '15/03/1962']);

it('stores an empty box as null', function () {
    Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => null])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSet('savedState.date', null);
});

it('rejects typed text with the message the browser shows', function (string $typedText, string $expectedMessage) {
    $component = Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => $typedText])
        ->call('save');

    expect($component->errors()->get('data.date'))->toBe([$expectedMessage])
        ->and($component->get('savedState'))->toBeNull();
})->with([
    'two-digit year' => ['15/3/62', 'Please enter the full year, e.g. 1962'],
    'no such date' => ['31/02/1962', "That date doesn't exist"],
    'not a date' => ['abc', 'Enter the date as dd/mm/yyyy, e.g. 15/03/1962'],
]);

it('keeps a past date out of the future', function () {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput->pastDate();

    $component = Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => '2026-10-06'])
        ->call('save');

    expect($component->errors()->get('data.date'))->toBe(["Can't be in the future"]);

    Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => '2026-10-05'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSet('savedState.date', '2026-10-05');
});

it('words the range messages in the display format', function (string $typedText, string $expectedMessage) {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput
        ->minDate('2020-01-01')
        ->maxDate(Carbon::parse('2020-12-31'));

    $component = Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => $typedText])
        ->call('save');

    expect($component->errors()->get('data.date'))->toBe([$expectedMessage]);
})->with([
    'before the minimum' => ['2019-12-31', 'Must be on or after 01/01/2020'],
    'after the maximum' => ['2021-01-01', 'Must be on or before 31/12/2020'],
]);

it('needs the Day level, because without it nothing can be picked', function () {
    expect(fn () => DateInput::make('date')->views([DateInputView::Decade, DateInputView::Year]))
        ->toThrow(InvalidArgumentException::class);
});

it('shows calendar levels coarse to fine whatever order they are given in', function () {
    $dateInput = DateInput::make('date')->views([DateInputView::Day, DateInputView::Decade]);

    expect($dateInput->getViews())->toBe([DateInputView::Decade, DateInputView::Day]);
});

it('opens at the first level it has when the start level is missing', function () {
    $dateInput = DateInput::make('date')
        ->views([DateInputView::Month, DateInputView::Day])
        ->startView(DateInputView::Decade);

    expect($dateInput->getStartView())->toBe(DateInputView::Month);
});

it('hands the browser the settings and the app\'s own today', function () {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput->pastDate()->required();

    Livewire::test(DateInputTestForm::class)
        ->assertFormFieldExists('date', function (DateInput $dateInput): bool {
            // Filament ids a field as "<schema name>.<field name>", not by state path.
            expect($dateInput->getClientConfig())->toMatchArray([
                'fieldId' => 'form.date',
                'displayFormat' => ['partOrder' => ['d', 'm', 'Y'], 'separator' => '/'],
                'views' => ['decade', 'year', 'month', 'day'],
                'startView' => 'decade',
                'decadesPerPage' => 12,
                'minDate' => null,
                'maxDate' => '2026-10-05',
                'firstDayOfWeek' => 1,
                'hasTodayButton' => false,
                'hasHelpPopover' => true,
                'isRequired' => true,
                'todayIso' => '2026-10-05',
                'messages' => [
                    'format' => 'Enter the date as dd/mm/yyyy, e.g. 15/03/1962',
                    'fullYear' => 'Please enter the full year, e.g. 1962',
                    'noSuchDate' => "That date doesn't exist",
                    'afterMax' => "Can't be in the future",
                    'beforeMin' => null,
                ],
            ]);

            return true;
        });
});

it('renders a disabled field as a plain, locked box', function () {
    DateInputTestForm::$configureDateInput = fn (DateInput $dateInput): DateInput => $dateInput->disabled();

    $renderedHtml = Livewire::test(DateInputTestForm::class)->html();

    expect($renderedHtml)
        ->toMatch('/<input(?=[^>]*\bdisabled\b)(?=[^>]*fi-date-input-text)[^>]*>/')
        ->not->toContain('fi-date-input-calendar-button')
        ->not->toContain('fi-date-input-panel');
});

it('renders the box with its placeholder and calendar button', function () {
    $renderedHtml = Livewire::test(DateInputTestForm::class)->html();

    expect($renderedHtml)
        ->toContain('placeholder="dd/mm/yyyy"')
        ->toContain('fi-date-input-calendar-button')
        ->toContain('Press ? for help');
});

it('asks password managers to leave the box alone', function () {
    $renderedHtml = Livewire::test(DateInputTestForm::class)->html();

    expect($renderedHtml)
        ->toMatch('/<input(?=[^>]*\bdata-1p-ignore\b)(?=[^>]*fi-date-input-text)[^>]*>/')
        ->toMatch('/<input(?=[^>]*\bdata-lpignore="true")(?=[^>]*fi-date-input-text)[^>]*>/')
        ->toMatch('/<input(?=[^>]*\bdata-bwignore\b)(?=[^>]*fi-date-input-text)[^>]*>/');
});

it('clears its server error once a corrected date is committed', function () {
    $component = Livewire::test(DateInputTestForm::class)
        ->fillForm(['date' => '15/3/62'])
        ->call('save');

    expect($component->errors()->get('data.date'))->toBe(['Please enter the full year, e.g. 1962']);

    $component->fillForm(['date' => '1962-03-15']);

    expect($component->errors()->get('data.date'))->toBe([]);
});

it('renders the help pop-up once per request, not once per schema pass', function () {
    $helpRenderCount = 0;
    View::composer('filament-date-input::help', function () use (&$helpRenderCount): void {
        $helpRenderCount++;
    });

    $component = Livewire::test(DateInputTestForm::class);
    expect($helpRenderCount)->toBe(1);

    $component->fillForm(['date' => '1962-03-15'])->call('save');
    expect($helpRenderCount)->toBe(3);
});
