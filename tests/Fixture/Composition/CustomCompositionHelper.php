<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormSession;
use FormCompose\Rendering\RenderContext;
use FormCompose\View\Helper\FormHelper;

/**
 * Installs specialized definition rendering through the helper's session factory.
 */
class CustomCompositionHelper extends FormHelper
{
    /**
     * Inject the layout collaborator without replacing binding or the field resolver.
     */
    protected function createSession(
        FormBinding $binding,
        RenderContext $rendering,
        BlueprintFactory $blueprints,
    ): FormSession {
        return new FormSession($binding, $rendering, $blueprints, new CustomBlueprintRenderer($rendering));
    }
}
