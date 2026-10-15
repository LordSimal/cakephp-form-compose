<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Defines how a control renders its inputs and describes their submitted names.
 *
 * NativeType implements standard browser controls. Applications implement this
 * interface for controls such as a date range that emits multiple inputs, then
 * register the instance in bind()'s types map and select it with a field's type.
 *
 * render() supplies markup; submissions() supplies the names and locked values
 * used by form protection. BoundField registers them after rendering succeeds.
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
