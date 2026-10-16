<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Field;
use FormCompose\Test\Fixture\Blueprint\Valid\InputForm;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Field('active', type: 'checkbox')]
class InputWithCheckboxForm extends InputForm
{
}
