<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\FieldTypeInterface;
use FormCompose\Form\Field\Submission;
use FormCompose\Rendering\Attributes;

/**
 * Test integration control with two actual submitted inputs.
 */
final class RangeType implements FieldTypeInterface
{
    /**
     * Emit both ends of a range with unique identities.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        $start = $attributes->replace(['name' => $field->state->name . '[start]', 'value' => '1']);
        $end = $attributes->replace([
            'name' => $field->state->name . '[end]',
            'id' => $field->state->id . '-end',
            'value' => '10',
        ]);

        return '<input' . (string)$start . '><input' . (string)$end . '>';
    }

    /**
     * Describe every submitted input independently of its presentation.
     *
     * @return list<\FormCompose\Form\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return [new Submission($field->state->name . '[start]'), new Submission($field->state->name . '[end]')];
    }
}
