<?php

namespace SixteenHands\FilamentDateInput\Tests\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use SixteenHands\FilamentDateInput\DateInput;

/**
 * A bare Livewire form holding one date input, so the field can be tested away
 * from any app's resources. A test shapes the field by setting $configureDateInput
 * before mounting.
 */
class DateInputTestForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var (Closure(DateInput): DateInput)|null */
    public static ?Closure $configureDateInput = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $dateInput = DateInput::make('date')->label('Date of Birth');

        if (static::$configureDateInput !== null) {
            $dateInput = (static::$configureDateInput)($dateInput);
        }

        return $schema
            ->components([$dateInput])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
            BLADE;
    }
}
