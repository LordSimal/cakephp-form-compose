<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Plugin\src\Form\Admin;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Ensures recursive discovery includes nested plugin form declarations.
 */
#[Form('BlueprintFixture.admin')]
class AdminProfile extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('admin_email')->type('email');
    }
}
