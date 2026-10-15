<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\DefinitionRenderer;
use FormCompose\DefinitionView;
use FormCompose\FormDefinition;
use FormCompose\FormSession;

/**
 * Supplies an application-specific scoped view through the public renderer dependency.
 */
class CustomDefinitionRenderer extends DefinitionRenderer
{
    /**
     * Preserve the original session and compiled definition in the specialized view.
     */
    protected function createView(FormSession $session, FormDefinition $definition, string $path): DefinitionView
    {
        return new CustomDefinitionView($session, $definition, $path);
    }
}
