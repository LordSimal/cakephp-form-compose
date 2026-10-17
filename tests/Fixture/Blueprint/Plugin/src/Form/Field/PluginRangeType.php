<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Plugin\src\Form\Field;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Test\Fixture\Blueprint\Valid\DiscoveredRangeType;

/**
 * Ensures application-level resolver defaults also discover plugin custom controls.
 */
#[AsFieldType('plugin-range')]
class PluginRangeType extends DiscoveredRangeType
{
}
