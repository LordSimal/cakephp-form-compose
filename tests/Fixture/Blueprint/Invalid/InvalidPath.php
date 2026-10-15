<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Exercises declaration validation through the public bind entry point.
 */
#[Field('bad[path]')]
class InvalidPath extends FormBlueprint
{
}
