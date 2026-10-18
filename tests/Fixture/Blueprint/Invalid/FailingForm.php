<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use RuntimeException;

/**
 * Proves runtime hook failures do not leak partially configured defaults.
 */
class FailingForm extends FormBlueprint
{
    /**
     * Fail after adding a field to an otherwise independent builder.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('partial');
        throw new RuntimeException('Configuration failed.');
    }
}
