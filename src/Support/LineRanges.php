<?php

namespace Webwizardsusa\RichEditorPlusCode\Support;

/**
 * The lines a code block highlights, written as `2,4-6`: line numbers and ranges from 1, separated by commas. Input is
 * normalized on the way in, so stored content only ever holds sorted, merged ranges within the limits below.
 */
final class LineRanges
{
    /**
     * The highest line number a range can reach. A larger end is cut to it, so `1-999999` can't make huge loops.
     */
    public const int MAX_LINE = 10_000;

    /**
     * How many ranges one block keeps, after merging.
     */
    public const int MAX_RANGES = 50;

    /**
     * Whether the text is a valid list of lines and ranges, such as `2,4-6`. Blank counts as valid: it means none.
     */
    public static function isValid(?string $lines): bool
    {
        return blank($lines) || preg_match('/^\s*\d+(\s*-\s*\d+)?(\s*,\s*\d+(\s*-\s*\d+)?)*\s*$/', $lines) === 1;
    }

    /**
     * The ranges as sorted, merged `[first, last]` pairs (1-based, inclusive). Invalid input, a zero or a range that
     * ends before it starts gives none.
     *
     * @return list<array{int, int}>
     */
    public static function parse(mixed $lines): array
    {
        if (! is_string($lines) || ! self::isValid($lines) || blank($lines)) {
            return [];
        }

        $ranges = [];

        foreach (explode(',', $lines) as $part) {
            $bounds = array_map(intval(...), explode('-', $part));
            $first = $bounds[0];
            $last = $bounds[1] ?? $first;

            if ($first < 1 || $last < $first || $first > self::MAX_LINE) {
                continue;
            }

            $ranges[] = [$first, min($last, self::MAX_LINE)];
        }

        sort($ranges);

        $merged = [];

        foreach ($ranges as [$first, $last]) {
            $previous = array_key_last($merged);

            if ($previous !== null && $first <= $merged[$previous][1] + 1) {
                $merged[$previous][1] = max($merged[$previous][1], $last);

                continue;
            }

            $merged[] = [$first, $last];
        }

        return array_slice($merged, 0, self::MAX_RANGES);
    }

    /**
     * The normalized text, such as `2,4-6`, or null for none.
     */
    public static function normalize(mixed $lines): ?string
    {
        $ranges = self::parse($lines);

        if ($ranges === []) {
            return null;
        }

        return implode(',', array_map(
            static fn (array $range): string => $range[0] === $range[1] ? (string) $range[0] : "{$range[0]}-{$range[1]}",
            $ranges,
        ));
    }
}
