<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Rendering\Attributes;

/**
 * Describes additional DOM identities emitted by a field type's successful render.
 *
 * Implement alongside FieldTypeInterface for composite controls. Return every
 * additional emitted input/option ID; BoundField reserves them in the form's render transaction.
 * Types without this interface retain the bound field's main input ID check.
 */
interface InputIdentityProviderInterface
{
    /**
     * Return additional IDs without rendering or registering again.
     * The bound field's main ID is already reserved; do not repeat it here.
     *
     * @return list<string>
     */
    public function inputIds(BoundField $field, Attributes $attributes): array;
}
