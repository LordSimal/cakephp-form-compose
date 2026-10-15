<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Prepares the hidden input used when a choice control submits no selection.
 *
 * Browsers omit unchecked checkboxes and empty multiple selections. NativeType
 * renders this fallback before the visible control so an unchecked checkbox can
 * submit its uncheckedValue (zero by default), or an empty choice group can submit
 * an empty string. The hiddenField option disables this behavior.
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
