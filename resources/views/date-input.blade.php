@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;

    $clientConfig = $getClientConfig();
    $fieldId = $getId();
    $hasHelpPopover = $hasHelpPopover();
    $isDisabled = $isDisabled();
    $label = $getLabel();
    $livewireKey = $getLivewireKey();
    $statePath = $getStatePath();
    $messageId = $fieldId . '-date-input-message';
    $helpHintId = $fieldId . '-date-input-help-hint';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('date-input', '16hands/filament-date-input') }}"
        x-data="dateInputFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    config: @js($clientConfig),
                })"
        x-on:focusout="handleFocusOut($event)"
        x-on:submit.window="clientProblem = null"
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize($clientConfig)), 0, 64) }}"
        class="fi-date-input"
    >
        <x-filament::input.wrapper
            :disabled="$isDisabled"
            :valid="! $errors->has($statePath)"
        >
            <div class="fi-date-input-shell">
                <input
                    id="{{ $fieldId }}"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    data-1p-ignore
                    data-lpignore="true"
                    data-bwignore
                    placeholder="{{ $getPlaceholder() }}"
                    aria-describedby="{{ $messageId }}{{ $hasHelpPopover ? ' ' . $helpHintId : '' }}"
                    x-bind:value="boxText"
                    x-bind:aria-invalid="clientProblem ? 'true' : 'false'"
                    x-on:input="handleInput($event)"
                    x-on:keydown="handleBoxKeydown($event)"
                    x-on:paste="handlePaste($event)"
                    @disabled($isDisabled)
                    class="fi-input fi-date-input-text"
                />

                @unless ($isDisabled)
                    <button
                        type="button"
                        aria-haspopup="dialog"
                        aria-label="{{ __('filament-date-input::date-input.calendar.open', ['label' => $label]) }}"
                        x-on:click="toggleCalendar()"
                        class="fi-date-input-calendar-button"
                    >
                        <x-filament::icon :icon="Heroicon::OutlinedCalendarDays" class="fi-date-input-calendar-icon" />
                    </button>
                @endunless
            </div>
        </x-filament::input.wrapper>

        @if ($hasHelpPopover)
            <span id="{{ $helpHintId }}" class="fi-date-input-sr-only">
                {{ __('filament-date-input::date-input.help.input_hint') }}
            </span>
        @endif

        <p
            id="{{ $messageId }}"
            x-cloak
            x-show="clientProblem"
            aria-live="polite"
            class="fi-date-input-message"
        >
            <span x-text="clientProblem"></span>

            @if ($hasHelpPopover)
                <span aria-hidden="true">·</span>
                <button type="button" x-on:click="openHelp()" class="fi-date-input-message-help-link">
                    {{ __('filament-date-input::date-input.help.link') }}
                </button>
            @endif
        </p>

        @unless ($isDisabled)
            <div
                x-ref="panel"
                x-cloak
                x-float.placement.bottom-start.offset.flip.shift.teleport="{ offset: 8 }"
                x-on:keydown="handlePanelKeydown($event)"
                role="dialog"
                aria-label="{{ __('filament-date-input::date-input.calendar.choose', ['label' => $label]) }}"
                class="fi-date-input-panel"
            >
                <div class="fi-date-input-panel-header">
                    <button
                        type="button"
                        aria-label="{{ __('filament-date-input::date-input.calendar.previous') }}"
                        x-on:click="pageBy(-1)"
                        class="fi-date-input-nav"
                    >‹</button>

                    <button
                        type="button"
                        x-on:click="goUpLevel()"
                        x-bind:disabled="! levelAbove(calendarView)"
                        class="fi-date-input-title"
                    >
                        <span x-text="calendarPage.title"></span>
                        <span
                            x-show="levelAbove(calendarView)"
                            x-text="'↑ ' + levelLabel(levelAbove(calendarView))"
                            class="fi-date-input-title-up"
                        ></span>
                    </button>

                    <button
                        type="button"
                        aria-label="{{ __('filament-date-input::date-input.calendar.next') }}"
                        x-on:click="pageBy(1)"
                        class="fi-date-input-nav"
                    >›</button>
                </div>

                <div aria-label="{{ __('filament-date-input::date-input.calendar.level') }}" class="fi-date-input-crumbs">
                    <template x-for="(level, levelIndex) in config.views" x-bind:key="level">
                        <span class="fi-date-input-crumb">
                            <span x-show="levelIndex > 0" aria-hidden="true">›</span>
                            <span
                                x-text="levelLabel(level)"
                                x-bind:aria-current="level === calendarView ? 'step' : null"
                            ></span>
                        </span>
                    </template>
                </div>

                <div x-show="calendarView === 'day'" class="fi-date-input-weekdays">
                    <template x-for="weekdayName in weekdayHeadings" x-bind:key="weekdayName">
                        <div x-text="weekdayName" class="fi-date-input-weekday"></div>
                    </template>
                </div>

                <div
                    role="grid"
                    x-bind:class="calendarView === 'day' ? 'fi-date-input-grid-days' : 'fi-date-input-grid-wide'"
                    class="fi-date-input-grid"
                >
                    {{-- Drilling down re-renders the grid and removes the clicked cell; .stop keeps
                         x-float from seeing that detached cell as a click outside and closing. --}}
                    <template x-for="calendarCell in calendarPage.cells" x-bind:key="calendarCell.key">
                        <button
                            type="button"
                            x-text="calendarCell.label"
                            x-on:click.stop="drillInto(calendarCell.value)"
                            x-bind:tabindex="calendarCell.hasTabStop ? 0 : -1"
                            x-bind:disabled="calendarCell.isDisabled"
                            x-bind:aria-pressed="calendarCell.isSelected ? 'true' : null"
                            x-bind:class="{
                                'fi-date-input-cell-outside': calendarCell.isOutside,
                                'fi-date-input-cell-today': calendarCell.isToday,
                                'fi-date-input-cell-selected': calendarCell.isSelected,
                                'fi-date-input-cell-holds-selection': calendarCell.holdsSelection,
                            }"
                            class="fi-date-input-cell"
                        ></button>
                    </template>
                </div>

                <div x-show="config.hasTodayButton || ! config.isRequired" class="fi-date-input-panel-footer">
                    <button
                        type="button"
                        x-show="config.hasTodayButton"
                        x-bind:disabled="! isWithinRange(config.todayIso)"
                        x-on:click="chooseDate(config.todayIso)"
                        class="fi-date-input-footer-button"
                    >{{ __('filament-date-input::date-input.calendar.today') }}</button>

                    <button
                        type="button"
                        x-show="! config.isRequired"
                        x-on:click="clearDate()"
                        class="fi-date-input-footer-button"
                    >{{ __('filament-date-input::date-input.calendar.clear') }}</button>
                </div>
            </div>
        @endunless
    </div>
</x-dynamic-component>
