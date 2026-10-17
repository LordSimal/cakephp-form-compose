<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\RenderContext;
use FormCompose\View\Helper\FormHelper;

/**
 * Loads the contact form's session through the standard helper binding API.
 */
class ContactFormHelper extends FormHelper
{
    /**
     * Use the application's session subclass for every bound form.
     */
    protected function createSession(
        FormBinding $binding,
        RenderContext $rendering,
        BlueprintFactory $blueprints,
    ): FormSession {
        return new ContactFormSession($binding, $rendering, $blueprints);
    }
}
