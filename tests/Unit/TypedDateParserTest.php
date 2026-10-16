<?php

use SixteenHands\FilamentDateInput\DateDisplayFormat;
use SixteenHands\FilamentDateInput\TypedDateParser;

/**
 * The typed-text cases. date-input.js must parse every one of them the same way;
 * an app that runs these through the browser keeps its copy of this file the same.
 *
 * @return array<string, array{0: array{typed: string, entry?: string, isoDate?: ?string, shown?: string, message?: string}}>
 */
function typedDateCasesFromFixture(): array
{
    $typedDateCases = json_decode(
        file_get_contents(dirname(__DIR__).'/Fixtures/typed-dates.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    return collect($typedDateCases)
        ->mapWithKeys(fn (array $typedDateCase): array => [json_encode($typedDateCase['typed']) => [$typedDateCase]])
        ->all();
}

it('reads typed text the way the browser does', function (array $typedDateCase) {
    $parsedDate = (new TypedDateParser(DateDisplayFormat::fromFormat('d/m/Y')))->parse($typedDateCase['typed']);

    if (array_key_exists('message', $typedDateCase)) {
        expect($parsedDate->isProblem())->toBeTrue()
            ->and($parsedDate->problemMessage)->toBe($typedDateCase['message']);

        return;
    }

    if ($typedDateCase['isoDate'] === null) {
        expect($parsedDate->isEmpty())->toBeTrue();

        return;
    }

    expect($parsedDate->isDate())->toBeTrue()
        ->and($parsedDate->isoDate)->toBe($typedDateCase['isoDate']);
})->with(typedDateCasesFromFixture());

it('reads digits in the order of a year-first display format', function () {
    $parser = new TypedDateParser(DateDisplayFormat::fromFormat('Y-m-d'));

    expect($parser->parse('19620315')->isoDate)->toBe('1962-03-15')
        ->and($parser->parse('1962.3.15')->isoDate)->toBe('1962-03-15');
});

it('reads parts in the order of a month-first display format', function () {
    $parser = new TypedDateParser(DateDisplayFormat::fromFormat('m/d/Y'));

    expect($parser->parse('03151962')->isoDate)->toBe('1962-03-15')
        ->and($parser->parse('3/15/1962')->isoDate)->toBe('1962-03-15')
        ->and($parser->parse('15/03/1962')->problemMessage)->toBe("That date doesn't exist");
});

it('gives its example in the display format', function () {
    $parser = new TypedDateParser(DateDisplayFormat::fromFormat('m/d/Y'));

    expect($parser->parse('nonsense')->problemMessage)->toBe('Enter the date as mm/dd/yyyy, e.g. 03/15/1962');
});
