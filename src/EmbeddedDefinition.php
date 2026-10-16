<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;

/**
 * Mounts resolved, relative field configuration at one association path.
 *
 * The parent owns ordering; the child retains its layout and removed fields.
 * Rendering qualifies paths without creating another session.
 *
 * @internal Compiled blueprint composition metadata.
 */
final readonly class EmbeddedDefinition
{
    /**
     * Validate the mount path using the ordinary field-path contract.
     *
     * @param array<string, mixed> $options Presentation overrides, leaving the child layout intact.
     */
    public function __construct(public string $path, public FormDefinition $definition, public array $options = [])
    {
        new FieldDefinition($path);
    }
}
