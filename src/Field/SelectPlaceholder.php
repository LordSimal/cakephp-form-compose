<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use InvalidArgumentException;

/**
 * Supplies an explicit empty selection for optional single-select controls.
 *
 * NativeType applies this to prepared choices once. empty accepts false, true
 * for a blank label, or a label string. Multiple selects never infer a placeholder.
 */
final class SelectPlaceholder
{
    /**
     * Prepend an empty option without replacing an application's existing one.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared options.
     * @return list<\FormCompose\Field\Choice>
     */
    public static function apply(FieldState $state, Attributes $attributes, array $choices): array
    {
        if ($state->type !== 'select') {
            return $choices;
        }

        /** @var mixed $empty */
        $empty = $state->options['empty'] ?? !$attributes->has('multiple') && !$attributes->has('required');
        if (!is_bool($empty) && !is_string($empty)) {
            throw new InvalidArgumentException('The empty option must be a boolean or a string.');
        }

        if ($empty === false || self::containsEmpty($choices)) {
            return $choices;
        }

        $placeholder = new Choice('item', $empty === true ? '' : $empty, new Attributes([
            'value' => '',
            'selected' => in_array($state->value, [null, ''], true) ? 'selected' : false,
        ]));

        return [$placeholder, ...$choices];
    }

    /**
     * Recognize explicit empty options even inside optgroups.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared options.
     */
    private static function containsEmpty(array $choices): bool
    {
        foreach ($choices as $choice) {
            if (
                $choice->kind === 'group'
                    ? self::containsEmpty($choice->children)
                    : $choice->attributes->values['value'] === ''
            ) {
                return true;
            }
        }

        return false;
    }
}
