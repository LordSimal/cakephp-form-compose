<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\View\Form\ContextInterface;

/**
 * Translates CakePHP context metadata into browser validation attributes.
 *
 * FieldResolver uses this when creating FieldState. Required status, maximum text
 * length, and numeric step come from the context; field options can set required,
 * disabled, readonly, and multiple. Server-side validation stays in the application.
 */
final class Constraints
{
    /**
     * Resolve browser constraints from context metadata and field options.
     *
     * @param array<string, mixed> $options Field overrides.
     * @return array<string, mixed>
     */
    public static function resolve(ContextInterface $context, string $path, string $type, array $options): array
    {
        $constraints = [
            'required' => $options['required'] ?? $context->isRequired(FieldPath::association($path) ?? $path) ?? false,
        ];
        $constraints += array_intersect_key($options, array_flip(['disabled', 'readonly', 'multiple']));
        $maxLength = $context->getMaxLength($path);
        if (
            $maxLength !== null
            && in_array($type, ['text', 'textarea', 'email', 'password', 'search', 'tel', 'url'], true)
        ) {
            $constraints['maxlength'] = $maxLength;
        }

        if ($type === 'number') {
            $constraints['step'] = in_array($context->type($path), ['decimal', 'float'], true) ? 'any' : '1';
        }

        if (in_array($type, ['time', 'datetime-local'], true)) {
            $constraints['step'] = in_array(
                $context->type($path),
                ['datetimefractional', 'timestampfractional', 'timestamptimezone'],
                true,
            )
                ? '0.001'
                : '1';
        }

        if ($type === 'hidden' || (isset($constraints['disabled']) && $constraints['disabled'] !== false)) {
            $constraints['required'] = false;
        }

        return $constraints;
    }
}
