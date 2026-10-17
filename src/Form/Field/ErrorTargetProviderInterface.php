<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Rendering\Attributes;

/**
 * Supplies an actual focus target for a successfully rendered composite control.
 *
 * Implement alongside FieldTypeInterface when the main bound ID is not the right
 * summary target. Return null for an unlinked message; never render or register here.
 */
interface ErrorTargetProviderInterface
{
    /**
     * Return an emitted control ID, excluding the fragment marker, or no target.
     */
    public function errorTarget(BoundField $field, Attributes $attributes): ?string;
}
