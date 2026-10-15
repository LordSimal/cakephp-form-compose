<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Groups reusable FieldDefinition objects in their intended rendering order.
 *
 * Pass a definition to FormHelper::bind(), then call FormSession::render() to render
 * its fields together. Individual fields remain available through field() and
 * control(), with per-call options overriding the definition. Each field path must
 * be unique within the collection.
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
