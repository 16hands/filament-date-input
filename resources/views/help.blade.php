@php
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Js;

    $helpContent = $dateInput->getHelpContent();
    $fieldId = $dateInput->getId();
@endphp

<x-filament::dropdown
    placement="bottom-end"
    teleport
    width="xs"
    x-on:filament-date-input-open-help.window="if ($event.detail.fieldId === {{ Js::from($fieldId) }}) open($refs.helpTrigger)"
    x-on:filament-date-input-close-help.window="if ($event.detail.fieldId === {{ Js::from($fieldId) }}) close()"
    class="fi-date-input-help"
>
    <x-slot name="trigger">
        <button
            type="button"
            tabindex="-1"
            x-ref="helpTrigger"
            aria-expanded="false"
            aria-label="{{ __('filament-date-input::date-input.help.trigger', ['label' => $dateInput->getLabel()]) }}"
            class="fi-date-input-help-trigger"
        >
            <x-filament::icon :icon="Heroicon::OutlinedQuestionMarkCircle" class="fi-date-input-help-icon" />
        </button>
    </x-slot>

    <div
        data-date-input-help-for="{{ $fieldId }}"
        role="dialog"
        aria-label="{{ __('filament-date-input::date-input.help.title') }}"
        class="fi-date-input-help-panel"
    >
        <p class="fi-date-input-help-title">{{ __('filament-date-input::date-input.help.title') }}</p>

        <p class="fi-date-input-help-heading">{{ __('filament-date-input::date-input.help.type_heading') }}</p>
        <dl class="fi-date-input-help-rows">
            <dt><kbd>{{ $helpContent['digitsExample'] }}</kbd></dt>
            <dd>→ {{ $helpContent['example'] }}</dd>
            <dt><kbd>{{ $helpContent['singleDigitsExample'] }}</kbd></dt>
            <dd>{{ __('filament-date-input::date-input.help.single_digits') }}</dd>
            <dt><kbd>{{ $helpContent['otherSeparatorExample'] }}</kbd></dt>
            <dd>{{ __('filament-date-input::date-input.help.other_separators') }}</dd>
        </dl>
        <p class="fi-date-input-help-line">
            {{ __('filament-date-input::date-input.help.full_year', ['year' => $helpContent['year'], 'short_year' => $helpContent['shortYear']]) }}
        </p>
        <p class="fi-date-input-help-line">{{ __('filament-date-input::date-input.help.finish') }}</p>
        <p class="fi-date-input-help-line">
            <kbd>?</kbd> {{ __('filament-date-input::date-input.help.open_help') }}
        </p>

        <p class="fi-date-input-help-heading">{{ __('filament-date-input::date-input.help.pick_heading') }}</p>
        <p class="fi-date-input-help-line">
            {{ __('filament-date-input::date-input.help.pick_levels', ['levels' => $helpContent['levels']]) }}
        </p>
        @if ($helpContent['hasSeveralLevels'])
            <p class="fi-date-input-help-line">{{ __('filament-date-input::date-input.help.pick_up') }}</p>
        @endif

        <p class="fi-date-input-help-heading">{{ __('filament-date-input::date-input.help.keys_heading') }}</p>
        <dl class="fi-date-input-help-rows">
            @foreach (__('filament-date-input::date-input.help.keys') as $keyName => $keyAction)
                <dt><kbd>{{ $keyName }}</kbd></dt>
                <dd>{{ $keyAction }}</dd>
            @endforeach
        </dl>

        @if (filled($helpContent['fieldNote']))
            <p class="fi-date-input-help-field-note">{{ $helpContent['fieldNote'] }}</p>
        @endif
    </div>
</x-filament::dropdown>
