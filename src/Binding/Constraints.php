<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\View\Form\ContextInterface;

/**
 * Converts existing Cake context metadata into native browser constraints.
 */
final class Constraints
{
    /**
     * Resolve browser constraints without creating a second validation system.
     *
     * @param array<string, mixed> $options Field overrides.
     * @return array<string, mixed>
     */
    public static function resolve(ContextInterface $context, string $path, string $type, array $options): array
    {
        $constraints = ['required' => $options['required'] ?? $context->isRequired($path) ?? false];
        $constraints += array_intersect_key($options, array_flip(['disabled', 'readonly', 'multiple']));
        $maxLength = $context->getMaxLength($path);
        if ($maxLength !== null && in_array($type, ['text', 'email', 'password', 'search', 'tel', 'url'], true)) {
            $constraints['maxlength'] = $maxLength;
        }
        if ($type === 'number') {
            $constraints['step'] = in_array($context->type($path), ['decimal', 'float'], true) ? 'any' : '1';
        }
        if ($type === 'hidden' || (isset($constraints['disabled']) && $constraints['disabled'] !== false)) {
            $constraints['required'] = false;
        }

        return $constraints;
    }
}
