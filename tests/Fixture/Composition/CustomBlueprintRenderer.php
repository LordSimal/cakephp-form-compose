<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Blueprint\BlueprintRenderer;
use FormCompose\Blueprint\BlueprintView;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Form\FormSession;

/**
 * Supplies an application-specific scoped view through the public renderer dependency.
 */
class CustomBlueprintRenderer extends BlueprintRenderer
{
    /**
     * Preserve the original session and compiled definition in the specialized view.
     */
    protected function createView(FormSession $session, FormDefinition $definition, string $path): BlueprintView
    {
        return new CustomBlueprintView($session, $definition, $path);
    }
}
