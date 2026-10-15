<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Collision;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Test\Fixture\Blueprint\Valid\DiscoveredRangeType;

/**
 * Second named implementation for type collision tests.
 */
#[AsFieldType('collision-range')]
class SecondType extends DiscoveredRangeType
{
}
