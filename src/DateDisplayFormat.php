<?php

namespace SixteenHands\FilamentDateInput;

use InvalidArgumentException;

/**
 * A date format people can type: day, month and year, each once, joined by one
 * separator. Its part order decides how typed digits are read; it also supplies
 * the placeholder and the examples used in messages and the help pop-up.
 */
final readonly class DateDisplayFormat
{
    /**
     * @param  list<'d'|'m'|'Y'>  $partOrder
     */
    private function __construct(
        public string $format,
        public array $partOrder,
        public string $separator,
    ) {}

    public static function fromFormat(string $format): self
    {
        if (preg_match('/^([dmY])([\/.\-])([dmY])\2([dmY])$/', $format, $formatParts) !== 1) {
            throw new InvalidArgumentException("Date input display format [{$format}] must be d, m and Y joined by one of / - .");
        }

        $partOrder = [$formatParts[1], $formatParts[3], $formatParts[4]];

        if (count(array_unique($partOrder)) !== 3) {
            throw new InvalidArgumentException("Date input display format [{$format}] must use d, m and Y exactly once each.");
        }

        return new self($format, $partOrder, $formatParts[2]);
    }

    public function placeholder(): string
    {
        return $this->joinParts(['d' => 'dd', 'm' => 'mm', 'Y' => 'yyyy']);
    }

    /**
     * How many digits each part takes when the date is typed without separators.
     *
     * @return list<int>
     */
    public function partLengths(): array
    {
        return array_map(static fn (string $part): int => $part === 'Y' ? 4 : 2, $this->partOrder);
    }

    public function formatIsoDate(string $isoDate): string
    {
        [$year, $month, $day] = explode('-', $isoDate);

        return $this->joinParts(['d' => $day, 'm' => $month, 'Y' => $year]);
    }

    public function exampleDate(): string
    {
        return $this->joinParts(['d' => '15', 'm' => '03', 'Y' => '1962']);
    }

    public function exampleDigits(): string
    {
        return str_replace($this->separator, '', $this->exampleDate());
    }

    public function exampleSingleDigits(): string
    {
        return $this->joinParts(['d' => '1', 'm' => '3', 'Y' => '1962']);
    }

    public function exampleWithOtherSeparator(): string
    {
        $otherSeparator = $this->separator === '-' ? '.' : '-';

        return str_replace($this->separator, $otherSeparator, $this->exampleDate());
    }

    public function exampleYear(): string
    {
        return '1962';
    }

    public function exampleShortYear(): string
    {
        return '62';
    }

    /**
     * @param  array{d: string, m: string, Y: string}  $partValues
     */
    private function joinParts(array $partValues): string
    {
        return implode(
            $this->separator,
            array_map(static fn (string $part): string => $partValues[$part], $this->partOrder),
        );
    }
}
