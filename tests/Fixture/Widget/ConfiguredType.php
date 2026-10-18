<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;

/**
 * Exercises native child protocols without replacing the actual rendering pipeline.
 */
class ConfiguredType extends CompositeType
{
    /**
     * Capture the declaration used by this test control.
     *
     * @param list<\FormCompose\Form\Field\FieldSpec> $fields Relative child fields.
     */
    public function __construct(protected readonly array $fields)
    {
    }

    /**
     * Expose native, nested, or intentionally invalid declarations to the resolver.
     *
     * @return list<\FormCompose\Form\Field\FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        return $this->fields;
    }
}
