<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Rendering\Attributes;

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
     * @param list<\FormCompose\Form\Field\Choice> $choices Prepared options.
     * @return list<\FormCompose\Form\Field\Choice>
     */
    public static function apply(FieldState $state, Attributes $attributes, array $choices): array
    {
        if ($state->type !== 'select') {
            return $choices;
        }

        $empty = $state->config->placeholder ?? !$attributes->has('multiple') && !$attributes->has('required');

        if ($empty === false) {
            return $choices;
        }

        $retained = self::retainEmpty($choices, $state, $attributes);
        if ($retained !== null) {
            return $retained;
        }

        $placeholder = new Choice('item', $empty === true ? '' : $empty, new Attributes([
            'value' => '',
            'selected' => in_array($state->value, [null, ''], true) ? 'selected' : false,
        ]));

        return [$placeholder, ...$choices];
    }

    /**
     * Retain an existing blank option, selecting it for a null single-select value.
     *
     * @param list<\FormCompose\Form\Field\Choice> $choices Prepared options.
     * @return list<\FormCompose\Form\Field\Choice>|null
     */
    private static function retainEmpty(array $choices, FieldState $state, Attributes $attributes): ?array
    {
        foreach ($choices as $index => $choice) {
            if ($choice->kind === 'group') {
                $children = self::retainEmpty($choice->children, $state, $attributes);
                if ($children === null) {
                    continue;
                }

                $choices[$index] = new Choice(
                    $choice->kind,
                    $choice->label,
                    $choice->attributes,
                    $children,
                    $choice->presentation,
                );
            } elseif ($choice->attributes->values['value'] === '') {
                $choices[$index] = new Choice(
                    $choice->kind,
                    $choice->label,
                    $state->value === null && !$attributes->has('multiple')
                        ? $choice->attributes->replace(['selected' => 'selected'])
                        : $choice->attributes,
                    $choice->children,
                    $choice->presentation,
                );
            } else {
                continue;
            }

            return $choices;
        }

        return null;
    }
}
