<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Attribute\Form;

/**
 * Attributes cannot register unrelated classes as blueprints.
 */
#[Form('invalid')]
class NotAForm
{
}
