<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Blueprint\Valid\InputForm;

/**
 * Supplies a minimal blueprint for application authoring integration tests.
 */
class SecureForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embed('author', IdForm::class);
        $form->embed('inputs', InputForm::class);
    }
}
