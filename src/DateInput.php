<?php

namespace SixteenHands\FilamentDateInput;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * A date field people can type straight into (separators appear as they type)
 * or pick from a decade › year › month › day calendar. Livewire state is always
 * Y-m-d or null; anything else is typed text the server will reject.
 */
class DateInput extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament-date-input::date-input';

    protected string|Closure $displayFormat = 'd/m/Y';

    /**
     * @var array<DateInputView> | Closure
     */
    protected array|Closure $views = [
        DateInputView::Decade,
        DateInputView::Year,
        DateInputView::Month,
        DateInputView::Day,
    ];

    protected DateInputView|Closure $startView = DateInputView::Day;

    protected int|Closure $decadesPerPage = 12;

    protected CarbonInterface|string|Closure|null $minDate = null;

    protected CarbonInterface|string|Closure|null $maxDate = null;

    protected int|Closure $firstDayOfWeek = 1;

    protected bool|Closure $hasTodayButton = true;

    protected bool|Closure $hasHelpPopover = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder(static fn (DateInput $component): string => $component->getDateDisplayFormat()->placeholder());

        $this->rule(static fn (DateInput $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $problemMessage = $component->getProblemForState($value);

            if ($problemMessage !== null) {
                $fail($problemMessage);
            }
        });

        // A corrected date must not keep showing the rejection from the last
        // save: Livewire keeps validation errors until something clears them.
        $this->afterStateUpdated(static function (DateInput $component): void {
            $component->getLivewire()->resetErrorBag($component->getStatePath());
        });

        $this->afterLabel(static fn (DateInput $component): ?Htmlable => $component->hasHelpPopover()
            ? $component->makeHelpPopover()
            : null);
    }

    public function displayFormat(string|Closure $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    /**
     * @param  array<DateInputView> | Closure  $views
     */
    public function views(array|Closure $views): static
    {
        if (is_array($views)) {
            static::ensureDayIsIncluded($views);
        }

        $this->views = $views;

        return $this;
    }

    public function startView(DateInputView|Closure $view): static
    {
        $this->startView = $view;

        return $this;
    }

    public function decadesPerPage(int|Closure $decadeCount): static
    {
        $this->decadesPerPage = $decadeCount;

        return $this;
    }

    public function minDate(CarbonInterface|string|Closure|null $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(CarbonInterface|string|Closure|null $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    public function firstDayOfWeek(int|Closure $dayOfWeek): static
    {
        $this->firstDayOfWeek = $dayOfWeek;

        return $this;
    }

    public function todayButton(bool|Closure $condition = true): static
    {
        $this->hasTodayButton = $condition;

        return $this;
    }

    public function helpPopover(bool|Closure $condition = true): static
    {
        $this->hasHelpPopover = $condition;

        return $this;
    }

    /**
     * A date of birth or death: the calendar opens at decades, nothing after
     * today is allowed, and there is no Today button.
     */
    public function pastDate(): static
    {
        return $this
            ->startView(DateInputView::Decade)
            ->maxDate(static fn (): CarbonInterface => Carbon::today())
            ->todayButton(false);
    }

    /**
     * The date in a date input's Livewire state, or null when the state is
     * empty or is typed text that isn't a date. Use it in afterStateUpdated()
     * before doing arithmetic on the value.
     */
    public static function dateFromState(mixed $state): ?CarbonImmutable
    {
        if (! is_string($state) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $state, $isoParts) !== 1) {
            return null;
        }

        if (! checkdate((int) $isoParts[2], (int) $isoParts[3], (int) $isoParts[1])) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $state, config('app.timezone'));
    }

    public function getDateDisplayFormat(): DateDisplayFormat
    {
        return DateDisplayFormat::fromFormat($this->evaluate($this->displayFormat));
    }

    public function getTypedDateParser(): TypedDateParser
    {
        return new TypedDateParser($this->getDateDisplayFormat());
    }

    /**
     * @return list<DateInputView>
     */
    public function getViews(): array
    {
        $views = $this->evaluate($this->views);

        static::ensureDayIsIncluded($views);

        return DateInputView::inCalendarOrder($views);
    }

    public function getStartView(): DateInputView
    {
        $startView = $this->evaluate($this->startView);
        $views = $this->getViews();

        return in_array($startView, $views, true) ? $startView : $views[0];
    }

    public function getDecadesPerPage(): int
    {
        return (int) $this->evaluate($this->decadesPerPage);
    }

    public function getMinIsoDate(): ?string
    {
        return static::toIsoDate($this->evaluate($this->minDate));
    }

    public function getMaxIsoDate(): ?string
    {
        return static::toIsoDate($this->evaluate($this->maxDate));
    }

    public function getTodayIsoDate(): string
    {
        return Carbon::today()->toDateString();
    }

    public function isMaxDateToday(): bool
    {
        return $this->getMaxIsoDate() === $this->getTodayIsoDate();
    }

    public function getFirstDayOfWeek(): int
    {
        return (int) $this->evaluate($this->firstDayOfWeek);
    }

    public function hasTodayButton(): bool
    {
        return (bool) $this->evaluate($this->hasTodayButton);
    }

    public function hasHelpPopover(): bool
    {
        return (bool) $this->evaluate($this->hasHelpPopover);
    }

    public function getProblemForState(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $parsedDate = $this->getTypedDateParser()->parse((string) $state);

        if ($parsedDate->isProblem()) {
            return $parsedDate->problemMessage;
        }

        if (! $parsedDate->isDate()) {
            return null;
        }

        return $this->getRangeProblemFor($parsedDate->isoDate);
    }

    public function getRangeProblemFor(string $isoDate): ?string
    {
        $maxIsoDate = $this->getMaxIsoDate();

        if ($maxIsoDate !== null && $isoDate > $maxIsoDate) {
            return $this->getAfterMaxMessage($maxIsoDate);
        }

        $minIsoDate = $this->getMinIsoDate();

        if ($minIsoDate !== null && $isoDate < $minIsoDate) {
            return $this->getBeforeMinMessage($minIsoDate);
        }

        return null;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            new DateInputStateCast($this->getDateDisplayFormat()),
        ];
    }

    /**
     * Everything the browser needs to behave exactly as the server validates:
     * the same messages, the same range, and the app's today rather than the
     * browser's clock.
     *
     * @return array{
     *     fieldId: string,
     *     displayFormat: array{partOrder: list<string>, separator: string},
     *     views: list<string>,
     *     startView: string,
     *     decadesPerPage: int,
     *     minDate: ?string,
     *     maxDate: ?string,
     *     firstDayOfWeek: int,
     *     hasTodayButton: bool,
     *     hasHelpPopover: bool,
     *     isRequired: bool,
     *     todayIso: string,
     *     messages: array{format: string, fullYear: string, noSuchDate: string, afterMax: ?string, beforeMin: ?string},
     *     labels: array{levels: array<string, string>, monthsLong: list<string>, monthsShort: list<string>, weekdaysShort: list<string>},
     * }
     */
    public function getClientConfig(): array
    {
        $displayFormat = $this->getDateDisplayFormat();
        $typedDateParser = $this->getTypedDateParser();
        $minIsoDate = $this->getMinIsoDate();
        $maxIsoDate = $this->getMaxIsoDate();

        return [
            'fieldId' => $this->getId(),
            'displayFormat' => [
                'partOrder' => $displayFormat->partOrder,
                'separator' => $displayFormat->separator,
            ],
            'views' => array_map(static fn (DateInputView $view): string => $view->value, $this->getViews()),
            'startView' => $this->getStartView()->value,
            'decadesPerPage' => $this->getDecadesPerPage(),
            'minDate' => $minIsoDate,
            'maxDate' => $maxIsoDate,
            'firstDayOfWeek' => $this->getFirstDayOfWeek(),
            'hasTodayButton' => $this->hasTodayButton(),
            'hasHelpPopover' => $this->hasHelpPopover(),
            'isRequired' => $this->isRequired(),
            'todayIso' => $this->getTodayIsoDate(),
            'messages' => [
                'format' => $typedDateParser->formatMessage(),
                'fullYear' => $typedDateParser->fullYearMessage(),
                'noSuchDate' => $typedDateParser->noSuchDateMessage(),
                'afterMax' => $maxIsoDate === null ? null : $this->getAfterMaxMessage($maxIsoDate),
                'beforeMin' => $minIsoDate === null ? null : $this->getBeforeMinMessage($minIsoDate),
            ],
            'labels' => [
                'levels' => array_combine(
                    array_map(static fn (DateInputView $view): string => $view->value, DateInputView::cases()),
                    array_map(static fn (DateInputView $view): string => $view->getLabel(), DateInputView::cases()),
                ),
                'monthsLong' => __('filament-date-input::date-input.calendar.months'),
                'monthsShort' => __('filament-date-input::date-input.calendar.months_short'),
                'weekdaysShort' => __('filament-date-input::date-input.calendar.weekdays_short'),
            ],
        ];
    }

    public function makeHelpPopover(): Htmlable
    {
        return new HelpPopover($this);
    }

    /**
     * @return array{example: string, digitsExample: string, singleDigitsExample: string, otherSeparatorExample: string, year: string, shortYear: string, levels: string, hasSeveralLevels: bool, fieldNote: ?string}
     */
    public function getHelpContent(): array
    {
        $displayFormat = $this->getDateDisplayFormat();
        $views = $this->getViews();

        return [
            'example' => $displayFormat->exampleDate(),
            'digitsExample' => $displayFormat->exampleDigits(),
            'singleDigitsExample' => $displayFormat->exampleSingleDigits(),
            'otherSeparatorExample' => $displayFormat->exampleWithOtherSeparator(),
            'year' => $displayFormat->exampleYear(),
            'shortYear' => $displayFormat->exampleShortYear(),
            'levels' => implode(' → ', array_map(
                static fn (DateInputView $view): string => mb_strtolower($view->getLabel()),
                $views,
            )),
            'hasSeveralLevels' => count($views) > 1,
            'fieldNote' => $this->getHelpFieldNote(),
        ];
    }

    /**
     * The help pop-up's last line: what is particular to this field. Null when
     * nothing is, so the line is left out.
     */
    public function getHelpFieldNote(): ?string
    {
        $displayFormat = $this->getDateDisplayFormat();
        $maxIsoDate = $this->getMaxIsoDate();
        $minIsoDate = $this->getMinIsoDate();
        $startView = $this->getStartView();
        $fieldNotes = [];

        if ($maxIsoDate !== null) {
            $fieldNotes[] = $this->isMaxDateToday()
                ? __('filament-date-input::date-input.help.note_in_future')
                : __('filament-date-input::date-input.help.note_on_or_before', ['date' => $displayFormat->formatIsoDate($maxIsoDate)]);
        }

        if ($minIsoDate !== null) {
            $fieldNotes[] = __('filament-date-input::date-input.help.note_on_or_after', ['date' => $displayFormat->formatIsoDate($minIsoDate)]);
        }

        if ($startView !== DateInputView::Day) {
            $fieldNotes[] = __('filament-date-input::date-input.help.note_opens_at', [
                'level' => __("filament-date-input::date-input.level_plurals.{$startView->value}"),
            ]);
        }

        if ($fieldNotes === []) {
            return null;
        }

        return __('filament-date-input::date-input.help.field_note', ['notes' => implode('; ', $fieldNotes)]);
    }

    private function getAfterMaxMessage(string $maxIsoDate): string
    {
        if ($this->isMaxDateToday()) {
            return __('filament-date-input::date-input.messages.in_future');
        }

        return __('filament-date-input::date-input.messages.after_max', [
            'date' => $this->getDateDisplayFormat()->formatIsoDate($maxIsoDate),
        ]);
    }

    private function getBeforeMinMessage(string $minIsoDate): string
    {
        return __('filament-date-input::date-input.messages.before_min', [
            'date' => $this->getDateDisplayFormat()->formatIsoDate($minIsoDate),
        ]);
    }

    private static function toIsoDate(CarbonInterface|string|null $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        $carbonDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date);

        return $carbonDate->setTimezone(config('app.timezone'))->toDateString();
    }

    /**
     * @param  array<DateInputView>  $views
     */
    private static function ensureDayIsIncluded(array $views): void
    {
        if (! in_array(DateInputView::Day, $views, true)) {
            throw new InvalidArgumentException('A date input needs the Day view: without it no date can be picked.');
        }
    }
}
