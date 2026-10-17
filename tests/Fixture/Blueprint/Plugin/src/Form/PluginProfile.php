<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Plugin\src\Form;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Mimics a loaded plugin exporting a namespaced form alias from its src/Form tree.
 */
#[Form('BlueprintFixture.profile')]
#[Field('plugin_email', type: 'email')]
class PluginProfile extends FormBlueprint
{
}
