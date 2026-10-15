<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Prepares native control attributes independently of markup and security registration.
 */
final class NativeAttributes
{
    /**
     * Resolve native input identity, selection, and browser value semantics.
     */
    public static function resolve(FieldState $state, Attributes $attributes): Attributes
    {
        if ($state->type === 'checkbox') {
            $value = NativeValue::scalar($state->options['checkedValue'] ?? '1');
            $selected = NativeValue::scalar($state->value) === $value;

            return $attributes->replace([
                'type' => 'checkbox',
                'value' => $value,
                'checked' => $attributes->values['checked'] ?? ($selected ? 'checked' : false),
            ]);
        }
        if (
            in_array($state->type, ['select', 'multicheckbox', 'file'], true)
            && (
                $attributes->has('multiple')
                || $state->type === 'multicheckbox'
            )
        ) {
            $attributes = $attributes->replace(['name' => InputNames::base($state->name) . '[]']);
        }
        if (in_array($state->type, ['select', 'textarea', 'radio', 'multicheckbox'], true)) {
            return $attributes;
        }

        return $attributes->replace(['type' => $state->type, 'value' => NativeValue::input($state)]);
    }
}
