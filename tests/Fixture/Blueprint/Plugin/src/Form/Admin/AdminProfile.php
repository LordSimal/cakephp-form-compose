<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Plugin\src\Form\Admin;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Ensures recursive discovery includes nested plugin form declarations.
 */
#[Form('BlueprintFixture.admin')]
#[Field('admin_email', type: 'email')]
class AdminProfile extends FormBlueprint
{
}
