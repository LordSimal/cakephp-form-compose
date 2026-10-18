<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Supplies an independent field collection for composition within a shared form session.
 */
#[Form('billing')]
class BillingAddressForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('billing.street')->label('Billing street');
        $form->add('billing.city')->label('Billing city')->help('Your <city>');
    }
}
