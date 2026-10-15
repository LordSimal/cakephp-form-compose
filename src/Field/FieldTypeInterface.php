<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Extension boundary for custom controls and their actual submitted inputs.
 */
interface FieldTypeInterface
{
    /**
     * Render the input without changing the form's security state.
     */
    public function render(BoundField $field, Attributes $attributes): string;

    /**
     * Describe fields emitted by this control after successful rendering.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array;
}
