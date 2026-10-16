<?php

namespace SixteenHands\FilamentDateInput;

use Illuminate\Contracts\Support\Htmlable;

/**
 * A date input's help pop-up, rendered only when Filament prints the label row.
 * Filament rebuilds a field's afterLabel content on every pass over the schema
 * (hydrate, validate, dehydrate, render…), so rendering the view up front would
 * render it several times per request for every date field on the form.
 */
final class HelpPopover implements Htmlable
{
    private ?string $renderedHtml = null;

    public function __construct(private readonly DateInput $dateInput) {}

    public function toHtml(): string
    {
        return $this->renderedHtml ??= view('filament-date-input::help', ['dateInput' => $this->dateInput])->render();
    }
}
