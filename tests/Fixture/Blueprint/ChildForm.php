<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;

/**
 * Extends inherited declarations without losing unrelated parent options or order.
 */
class ChildForm extends ProfileForm
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        parent::configure($form, $context);
        $form->field('email')->label('Child email');
        $form->add('phone')->type('tel');
    }
}
