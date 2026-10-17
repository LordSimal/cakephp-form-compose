<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Supplies an independent field collection for composition within a shared form session.
 */
#[Form('billing')]
#[Field('billing.street', label: 'Billing street')]
#[Field('billing.city', label: 'Billing city', help: 'Your <city>')]
class BillingAddressForm extends FormBlueprint
{
}
