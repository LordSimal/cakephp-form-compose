<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Configuration\PresentationConfig;
use FormCompose\Form\Field\FieldDefinition;

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
     */
    public function __construct(
        public string $path,
        public FormDefinition $definition,
        public PresentationConfig $presentation = new PresentationConfig(),
    ) {
        new FieldDefinition($path);
    }
}
