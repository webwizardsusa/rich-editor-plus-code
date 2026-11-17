<?php

use Webwizardsusa\RichEditorPlusCode\Support\LineRanges;

it('normalizes lines and ranges', function (?string $input, ?string $expected): void {
    expect(LineRanges::normalize($input))->toBe($expected);
})->with([
    'single line' => ['2', '2'],
    'sorted and spaced' => [' 4-6 , 2 ', '2,4-6'],
    'overlapping ranges merged' => ['1-3,2-5,7', '1-5,7'],
    'adjacent ranges merged' => ['1-2,3', '1-3'],
    'backwards range dropped' => ['5-3,1', '1'],
    'zero dropped' => ['0,2', '2'],
    'end capped' => ['1-999999', '1-'.LineRanges::MAX_LINE],
    'blank' => ['', null],
    'null' => [null, null],
    'not a list' => ['two', null],
    'trailing comma' => ['1,', null],
]);

it('keeps at most the range limit', function (): void {
    $lines = implode(',', range(1, LineRanges::MAX_RANGES * 4, 2));

    expect(LineRanges::parse($lines))->toHaveCount(LineRanges::MAX_RANGES);
});

it('accepts blank input as valid, meaning no lines', function (): void {
    expect(LineRanges::isValid(''))->toBeTrue()
        ->and(LineRanges::isValid('2, 4-6'))->toBeTrue()
        ->and(LineRanges::isValid('2;4'))->toBeFalse();
});
