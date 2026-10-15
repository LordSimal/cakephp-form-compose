<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Optional reusable field collection; validation and persistence remain application concerns.
 */
final readonly class FormDefinition
{
    /**
     * Collect uniquely named definitions in presentation order.
     *
     * @param list<\FormCompose\Field\FieldDefinition> $fields Fields to render.
     */
    public function __construct(public array $fields)
    {
        $paths = array_map(static fn(FieldDefinition $field): string => $field->path, $fields);
        if (count(array_unique($paths)) !== count($paths)) {
            throw new InvalidArgumentException('Form definitions cannot contain duplicate field paths.');
        }
    }
}
