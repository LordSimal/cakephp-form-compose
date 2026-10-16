<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Field('id')]
#[Field('summary')]
class DetailsFieldsForm extends FormBlueprint
{
}
