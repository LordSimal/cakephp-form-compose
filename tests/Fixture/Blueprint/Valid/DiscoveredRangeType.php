<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Field\BoundField;
use FormCompose\Field\FieldTypeInterface;
use FormCompose\Rendering\Attributes;
use FormCompose\Test\Fixture\RangeType;

/**
 * Discovers a composite input whose real submitted names must remain protected.
 */
#[AsFieldType('date-range')]
class DiscoveredRangeType implements FieldTypeInterface
{
    /**
     * Render both input names using the existing lightweight composite control.
     */
    public function render(BoundField $field, Attributes $attributes): string
    {
        return new RangeType()->render($field, $attributes);
    }

    /**
     * Describe both names independently of discovery and rendering.
     *
     * @return list<\FormCompose\Field\Submission>
     */
    public function submissions(BoundField $field, Attributes $attributes): array
    {
        return new RangeType()->submissions($field, $attributes);
    }
}
