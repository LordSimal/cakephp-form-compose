<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;

/**
 * Mounts resolved, relative field configuration at one association path.
 *
 * The parent owns ordering; the child retains its layout and removed fields.
 * Rendering qualifies paths without creating another session.
 */
final readonly class EmbeddedDefinition
{
    /**
     * Validate the mount path using the ordinary field-path contract.
     */
    public function __construct(public string $path, public FormDefinition $definition)
    {
        new FieldDefinition($path);
    }
}
