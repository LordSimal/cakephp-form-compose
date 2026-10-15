<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use RuntimeException;

/**
 * Proves runtime hook failures do not leak partially configured defaults.
 */
class FailingForm extends FormBlueprint
{
    /**
     * Fail after adding a field to an otherwise independent builder.
     */
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->add(FieldDefinition::make('partial'));
        throw new RuntimeException('Configuration failed.');
    }
}
