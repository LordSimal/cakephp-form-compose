<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Describes native empty submissions independently of their hidden-field templates.
 */
final class ControlFallback
{
    /**
     * Describe fallback submissions for unchecked or empty choice controls.
     */
    public static function resolve(FieldState $state, Attributes $attributes): ?Attributes
    {
        $needs =
            in_array($state->type, ['checkbox', 'radio', 'multicheckbox'], true)
            || ($state->type === 'select' && $attributes->has('multiple'));
        if (!$needs || !($state->options['hiddenField'] ?? true)) {
            return null;
        }

        return new Attributes([
            'type' => 'hidden',
            'name' => InputNames::base($state->name),
            'value' => $state->type === 'checkbox' ? NativeValue::scalar($state->options['uncheckedValue'] ?? '0') : '',
            'disabled' => $attributes->values['disabled'] ?? false,
            'form' => $attributes->values['form'] ?? null,
        ]);
    }
}
