<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Blueprint\Valid\InputForm;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
class InputWithCheckboxForm extends InputForm
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        parent::configure($form, $context);
        $form->add('active')->type('checkbox');
    }
}
