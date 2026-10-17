<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Summary;

use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\RenderContext;
use FormCompose\View\Helper\FormHelper;

/**
 * Injects a summary renderer through the public session construction boundary.
 */
class CustomHelper extends FormHelper
{
    protected function createSession(
        FormBinding $binding,
        RenderContext $rendering,
        BlueprintFactory $blueprints,
    ): FormSession {
        return new FormSession($binding, $rendering, $blueprints, summaries: new CustomRenderer($rendering));
    }
}
