<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

/**
 * Exercises runtime configuration failure before binding a partial form.
 */
class UnknownRemovalForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->remove('typo');
    }
}
