<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Attribute\Embed;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Closes the static composition cycle without requiring discovery aliases.
 */
#[Embed('first', CycleA::class)]
class CycleB extends FormBlueprint
{
}
