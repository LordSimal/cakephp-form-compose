<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;

/**
 * Reuses date input semantics across independent PHP layouts.
 */
#[AsFieldType('date-range')]
class DateRangeType extends CompositeType
{
    /**
     * Declare relative paths; Cake supplies values, constraints, and feedback.
     *
     * @return array<string, array<string, mixed>>
     */
    public function inputs(BoundField $field): array
    {
        return [
            'start' => ['type' => 'date', 'label' => 'Start date'],
            'end' => ['type' => 'date', 'label' => 'End date'],
        ];
    }
}
