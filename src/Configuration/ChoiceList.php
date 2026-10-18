<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ChoiceBuilder;
use FormCompose\Builder\ChoiceGroupBuilder;

/** Captures inline choice builders in data arrays without consuming lazy iterables. */
final class ChoiceList
{
    /**
     * @template TKey
     * @param iterable<TKey, mixed> $choices Maps, typed entries, or lazy choice sources.
     * @return iterable<TKey, mixed>
     */
    public static function capture(iterable $choices): iterable
    {
        return is_array($choices) ? array_map(self::item(...), $choices) : $choices;
    }

    /** Capture array entries now; lazy sources capture their yielded builders when rendered. */
    public static function item(mixed $choice): mixed
    {
        return match (true) {
            $choice instanceof ChoiceBuilder, $choice instanceof ChoiceGroupBuilder => $choice->config(),
            is_array($choice) => self::capture($choice),
            default => $choice,
        };
    }
}
