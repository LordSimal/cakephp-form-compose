<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;
use FormCompose\Form\Field\FieldSpec;

/**
 * Reuses date input semantics across independent PHP layouts.
 */
#[AsFieldType('date-range')]
class DateRangeType extends CompositeType
{
    /**
     * Declare relative paths; Cake supplies values, constraints, and feedback.
     *
     * @return list<\FormCompose\Form\Field\FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        return [
            new FieldSpec(
                'start',
                FieldConfig::builder()->type('date')->label('Start date'),
            ),
            new FieldSpec(
                'end',
                FieldConfig::builder()->type('date')->label('End date'),
            ),
        ];
    }
}
