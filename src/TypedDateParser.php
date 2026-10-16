<?php

namespace SixteenHands\FilamentDateInput;

/**
 * Reads what someone typed into a date input. The browser applies the same rules
 * (resources/js/date-input.js); tests/Fixtures/typed-dates.json is run through
 * both so the two can't drift apart.
 */
final readonly class TypedDateParser
{
    public function __construct(private DateDisplayFormat $displayFormat) {}

    public function parse(string $typedText): ParsedTypedDate
    {
        $trimmedText = trim($typedText);

        if ($trimmedText === '') {
            return ParsedTypedDate::empty();
        }

        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $trimmedText, $isoParts) === 1) {
            return $this->dateFromParts(year: $isoParts[1], month: $isoParts[2], day: $isoParts[3]);
        }

        if (preg_match('/^\d{8}$/', $trimmedText) === 1) {
            $trimmedText = $this->insertSeparators($trimmedText);
        }

        $typedParts = preg_split('/[\/\-. ]+/', $trimmedText, flags: PREG_SPLIT_NO_EMPTY);

        if (count($typedParts) !== 3 || preg_grep('/^\d+$/', $typedParts, PREG_GREP_INVERT) !== []) {
            return ParsedTypedDate::problem($this->formatMessage());
        }

        $partsByName = array_combine($this->displayFormat->partOrder, $typedParts);

        return $this->dateFromParts(year: $partsByName['Y'], month: $partsByName['m'], day: $partsByName['d']);
    }

    public function formatMessage(): string
    {
        return __('filament-date-input::date-input.messages.format', [
            'pattern' => $this->displayFormat->placeholder(),
            'example' => $this->displayFormat->exampleDate(),
        ]);
    }

    public function fullYearMessage(): string
    {
        return __('filament-date-input::date-input.messages.full_year', [
            'year' => $this->displayFormat->exampleYear(),
        ]);
    }

    public function noSuchDateMessage(): string
    {
        return __('filament-date-input::date-input.messages.no_such_date');
    }

    private function dateFromParts(string $year, string $month, string $day): ParsedTypedDate
    {
        if (strlen($year) !== 4 || (int) $year < 1000) {
            return ParsedTypedDate::problem($this->fullYearMessage());
        }

        if (! checkdate((int) $month, (int) $day, (int) $year)) {
            return ParsedTypedDate::problem($this->noSuchDateMessage());
        }

        return ParsedTypedDate::date(sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day));
    }

    private function insertSeparators(string $digits): string
    {
        $separatedParts = [];
        $digitOffset = 0;

        foreach ($this->displayFormat->partLengths() as $partLength) {
            $separatedParts[] = substr($digits, $digitOffset, $partLength);
            $digitOffset += $partLength;
        }

        return implode($this->displayFormat->separator, $separatedParts);
    }
}
