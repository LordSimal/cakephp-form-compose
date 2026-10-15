<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Collision;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Test\Fixture\Blueprint\Valid\DiscoveredRangeType;

/**
 * First named implementation for type collision tests.
 */
#[AsFieldType('collision-range')]
class FirstType extends DiscoveredRangeType
{
}
