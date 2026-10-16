<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Embed;
use FormCompose\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Embed('billing', InputWithCheckboxForm::class)]
class BillingInputsForm extends FormBlueprint
{
}
