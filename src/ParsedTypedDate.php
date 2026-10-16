<?php

namespace SixteenHands\FilamentDateInput;

/**
 * What a piece of typed text turned out to be: nothing, a date, or a problem
 * worded for the person who typed it.
 */
final readonly class ParsedTypedDate
{
    private function __construct(
        public ParsedTypedDateKind $kind,
        public ?string $isoDate = null,
        public ?string $problemMessage = null,
    ) {}

    public static function empty(): self
    {
        return new self(ParsedTypedDateKind::Empty);
    }

    public static function date(string $isoDate): self
    {
        return new self(ParsedTypedDateKind::Date, isoDate: $isoDate);
    }

    public static function problem(string $problemMessage): self
    {
        return new self(ParsedTypedDateKind::Problem, problemMessage: $problemMessage);
    }

    public function isEmpty(): bool
    {
        return $this->kind === ParsedTypedDateKind::Empty;
    }

    public function isDate(): bool
    {
        return $this->kind === ParsedTypedDateKind::Date;
    }

    public function isProblem(): bool
    {
        return $this->kind === ParsedTypedDateKind::Problem;
    }
}
