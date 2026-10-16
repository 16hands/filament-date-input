<?php

namespace SixteenHands\FilamentDateInput;

/**
 * The calendar's levels, coarsest first.
 */
enum DateInputView: string
{
    case Decade = 'decade';
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';

    public function getLabel(): string
    {
        return __("filament-date-input::date-input.levels.{$this->value}");
    }

    /**
     * The calendar always runs coarse to fine, whatever order a caller lists levels in.
     *
     * @param  array<DateInputView>  $views
     * @return list<DateInputView>
     */
    public static function inCalendarOrder(array $views): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (DateInputView $view): bool => in_array($view, $views, true),
        ));
    }
}
