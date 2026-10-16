# Filament Date Input

A type-first date field for Filament v5 forms.

- **Masked typing.** People type the date; separators appear as they type. `15031962`, `15/03/1962`, `1/3/1962`,
  `15-03-1962`, `15.03.1962` and `15 03 1962` all read as 15 March 1962 in the default `d/m/Y` format, and a pasted
  `1962-03-15` is accepted too. The year must be typed in full.
- **A decade › year › month › day calendar.** The calendar button (or Alt+↓) opens a picker that drills down from
  decades to the day. Clicking the heading goes back up a level. Arrow keys, Enter, Backspace, PgUp/PgDn and Esc work
  inside it.
- **A help pop-up.** A (?) beside the label (or `?` typed in the box) explains how to enter dates for that field,
  including its limits.

The browser and the server apply the same rules and show the same messages: "Please enter the full year, e.g. 1962",
"That date doesn't exist", "Enter the date as dd/mm/yyyy, e.g. 15/03/1962", and the range messages below.

## Requirements

- PHP 8.4
- Filament 5 (`filament/forms` ^5.0)

## Installation

```bash
composer require 16hands/filament-date-input
```

The package is not on Packagist yet. Until it is, add the GitHub repository to your app's `composer.json` before
requiring it:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/16hands/filament-date-input"
    }
]
```

The service provider is auto-discovered. Publish the script and stylesheet to `public/` after installing, and again
after every update:

```bash
php artisan filament:assets
```

The asset URLs carry a hash of each file's contents, so browsers fetch the new files once they are published.

Inside a Filament panel nothing else is needed. In a standalone Livewire form, the layout needs Filament's
`@filamentStyles` and `@filamentScripts`, as for any Filament form.

## Usage

```php
use SixteenHands\FilamentDateInput\DateInput;

DateInput::make('date_of_birth')
```

### Options

| Option | Default | What it does |
| --- | --- | --- |
| `->displayFormat('d/m/Y')` | `'d/m/Y'` | The format people type and see: `d`, `m` and `Y` once each, joined by one of `/`, `-` or `.`. It also decides the order typed digits are read in, the placeholder (`dd/mm/yyyy`) and the examples in messages and help. Any other format throws `InvalidArgumentException`. |
| `->views([...])` | Decade, Year, Month, Day | The calendar levels the picker has. `Day` is required (without it no date can be picked). Levels always run coarse to fine, whatever order they are given in. |
| `->startView(DateInputView::Decade)` | `Day` | The level the calendar opens at. If that level is not in `views()`, it opens at the coarsest level it has. |
| `->decadesPerPage(12)` | `12` | How many decades one page of the decade level shows. Pages are aligned so that one ends at the decade of the max date, or of today when there is none. |
| `->minDate('2020-01-01')` | none | The earliest date allowed. Earlier dates are rejected with "Must be on or after 01/01/2020" and disabled in the calendar. |
| `->maxDate('2020-12-31')` | none | The latest date allowed. Later dates are rejected with "Must be on or before 31/12/2020" (or "Can't be in the future" when the max date is today) and disabled in the calendar. |
| `->pastDate()` | | For a date of birth or death: opens at decades, `maxDate` today, no Today button. |
| `->firstDayOfWeek(1)` | `1` (Monday) | The weekday the day grid starts on: `0` Sunday to `6` Saturday. |
| `->todayButton(false)` | `true` | Shows or hides the calendar's Today button. It is disabled when today is outside the allowed range. |
| `->helpPopover(false)` | `true` | Shows or hides the (?) help pop-up beside the label and the "Press ? for help" hint. |

`minDate()` and `maxDate()` take a Carbon instance, a date string, a closure or `null`. Every option that takes a value
also accepts a closure, evaluated like any other Filament field option. The usual field methods (`label()`,
`required()`, `disabled()`, `placeholder()`, `live()`, …) work as on any Filament field. A field that is not required
gets a Clear button in the calendar.

A calendar without the decade level:

```php
use SixteenHands\FilamentDateInput\DateInput;
use SixteenHands\FilamentDateInput\DateInputView;

DateInput::make('starts_on')
    ->views([DateInputView::Year, DateInputView::Month, DateInputView::Day])
```

A date of birth:

```php
DateInput::make('date_of_birth')
    ->pastDate()
    ->required()
```

To give every date input in an app the same format, configure it once in a service provider:

```php
DateInput::configureUsing(function (DateInput $dateInput): void {
    $dateInput
        ->displayFormat('d/m/Y')
        ->firstDayOfWeek(1);
});
```

## Stored state

- The field's Livewire state is a `Y-m-d` string, `null` when the box is empty, or the text someone typed that is not a
  date. That text stays in the state so validation rejects it with the message the browser already showed.
- The value saved to the model (`$form->getState()`) is `Y-m-d` or `null`, whatever the display format.
- When a record is loaded, `Y-m-d` strings, datetime strings and Carbon / `DateTimeInterface` values (for example from a
  `date` cast) are all read as the calendar date in the app timezone (`config('app.timezone')`). MySQL zero dates
  (`0000-00-00`) load as empty.
- "Today", for `pastDate()`, the Today button and the calendar's highlight, is the server's today in the app timezone,
  not the browser's clock.

The state can still hold a typo while the person is editing, so check it before doing date arithmetic in
`afterStateUpdated()`:

```php
use Filament\Schemas\Components\Utilities\Set;
use SixteenHands\FilamentDateInput\DateInput;

DateInput::make('date_of_birth')
    ->live()
    ->afterStateUpdated(function (?string $state, Set $set): void {
        $dateOfBirth = DateInput::dateFromState($state); // CarbonImmutable, or null for empty or typo

        if ($dateOfBirth !== null) {
            $set('age', $dateOfBirth->age);
        }
    })
```

## Translations

All text (messages, calendar labels, month and weekday names, help) lives in the `filament-date-input::date-input`
translation group. English ships with the package. To change the wording or add a language, publish the files:

```bash
php artisan vendor:publish --tag=filament-date-input-translations
```

They land in `lang/vendor/filament-date-input/{locale}/date-input.php`.

The views can be published the same way with `--tag=filament-date-input-views`.

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE](LICENSE.md).
