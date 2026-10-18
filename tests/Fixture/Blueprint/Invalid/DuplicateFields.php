<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Rejects duplicate declarations on the same blueprint class.
 */
class DuplicateFields extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('email');
        $form->add('email');
    }
}
