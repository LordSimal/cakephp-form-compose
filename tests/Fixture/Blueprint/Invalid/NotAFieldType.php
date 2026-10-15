<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Attribute\AsFieldType;

/**
 * Attributes cannot register unrelated classes as controls.
 */
#[AsFieldType('invalid')]
class NotAFieldType
{
}
