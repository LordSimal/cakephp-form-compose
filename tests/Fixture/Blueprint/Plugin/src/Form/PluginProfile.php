<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Plugin\src\Form;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Mimics a loaded plugin exporting a namespaced form alias from its src/Form tree.
 */
#[Form('BlueprintFixture.profile')]
class PluginProfile extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('plugin_email')->type('email');
    }
}
