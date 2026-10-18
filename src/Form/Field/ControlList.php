<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Builder\ControlBuilder;
use InvalidArgumentException;

/** Validates ordered field authoring lists without binding or rendering their entries. */
final class ControlList
{
    /**
     * @param iterable<array-key, mixed> $fields Paths, immutable specs, or configured controls.
     * @return list<FieldSpec|ControlBuilder>
     */
    public static function normalize(iterable $fields): array
    {
        $result = [];
        /** @var mixed $field */
        foreach ($fields as $field) {
            if (is_string($field)) {
                $field = new FieldSpec($field);
            }

            if (!$field instanceof FieldSpec && !$field instanceof ControlBuilder) {
                throw new InvalidArgumentException(
                    'Use field paths, FieldSpec objects, or controls from this session.',
                );
            }

            $result[] = $field;
        }

        return $result;
    }
}
