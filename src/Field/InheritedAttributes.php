<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Propagates composite feedback and effective constraints to its real inputs.
 *
 * Child identity and presentation stay independent. A disabled or readonly parent
 * cannot be re-enabled by a child; child ARIA overrides retain normal null semantics.
 */
final class InheritedAttributes
{
    /**
     * Combine inherited feedback with a child's own prepared input attributes.
     */
    public static function resolve(Attributes $parent, Attributes $child, FieldState $state): Attributes
    {
        $feedback = $state->type === 'hidden'
            ? []
            : array_intersect_key($parent->values, array_flip(['aria-describedby', 'aria-invalid']));
        $attributes = new Attributes($feedback)->merge($child->values);
        foreach (['disabled', 'readonly'] as $constraint) {
            if (!$parent->has($constraint)) {
                continue;
            }

            $attributes = $attributes->replace([$constraint => true]);
        }

        return $attributes->has('disabled') ? $attributes->replace(['required' => false]) : $attributes;
    }
}
