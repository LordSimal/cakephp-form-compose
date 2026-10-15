<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use InvalidArgumentException;

/**
 * Registers a FieldTypeInterface implementation under a discoverable control name.
 *
 * Place implementations in src/Field/ or configure additional discovery paths.
 * Per-form types supplied to bind() override discovered implementations.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsFieldType
{
    /**
     * Name a control using the same syntax as explicit field types.
     */
    public function __construct(public string $name)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/D', $name)) {
            throw new InvalidArgumentException('Invalid control type alias.');
        }
    }
}
