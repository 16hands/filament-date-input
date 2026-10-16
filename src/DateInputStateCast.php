<?php

namespace SixteenHands\FilamentDateInput;

use Carbon\Exceptions\InvalidFormatException;
use DateTimeInterface;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Illuminate\Support\Carbon;

/**
 * Moves a date input's value between the model and Livewire. Livewire holds
 * Y-m-d, null, or text someone typed that isn't a date — that text is kept so
 * validation can reject it with the message the browser already showed.
 */
final readonly class DateInputStateCast implements StateCast
{
    public function __construct(private DateDisplayFormat $displayFormat) {}

    /**
     * Livewire state → the value saved on the model. Validation has already
     * rejected anything that isn't a date, so a valid date is all that reaches
     * here in practice; it is normalised to Y-m-d whichever way it was written.
     */
    public function get(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $parsedDate = (new TypedDateParser($this->displayFormat))->parse((string) $state);

        return $parsedDate->isDate() ? $parsedDate->isoDate : (string) $state;
    }

    /**
     * The model's value → Livewire state. A `date` cast reaches Livewire as a
     * UTC timestamp, so it is read back into the app timezone before taking the
     * calendar date; MySQL zero dates load as empty.
     */
    public function set(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_string($state)) {
            $storedText = trim($state);

            if (str_starts_with($storedText, '0000-00-00')) {
                return null;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $storedText) === 1) {
                return $storedText;
            }
        }

        if (! $state instanceof DateTimeInterface) {
            try {
                $state = Carbon::parse((string) $state);
            } catch (InvalidFormatException) {
                return (string) $state;
            }
        }

        $localDate = Carbon::instance($state)->setTimezone(config('app.timezone'));

        return $localDate->year < 1 ? null : $localDate->toDateString();
    }
}
