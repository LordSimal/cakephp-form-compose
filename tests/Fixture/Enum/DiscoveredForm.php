<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Leaves enum choice discovery to the bound schema, including scoped occurrences.
 */
#[Field('status')]
class DiscoveredForm extends FormBlueprint
{
}
