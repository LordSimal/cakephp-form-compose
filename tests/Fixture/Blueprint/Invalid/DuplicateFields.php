<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Rejects duplicate declarations on the same blueprint class.
 */
#[Field('email')]
#[Field('email')]
class DuplicateFields extends FormBlueprint
{
}
