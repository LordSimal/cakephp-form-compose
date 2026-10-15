<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Attribute\Embed;
use FormCompose\FormBlueprint;

/**
 * Starts an indirect cycle between differently mounted blueprint classes.
 */
#[Embed('peer', CycleB::class)]
class CycleA extends FormBlueprint
{
}
