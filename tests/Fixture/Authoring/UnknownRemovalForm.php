<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\BlueprintBuilder;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;

/**
 * Exercises runtime configuration failure before binding a partial form.
 */
class UnknownRemovalForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->remove('typo');
    }
}
