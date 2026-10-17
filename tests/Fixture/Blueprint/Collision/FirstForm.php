<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Collision;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

/**
 * First half of a discovery collision across different source files.
 */
#[Form('collision')]
class FirstForm extends FormBlueprint
{
}
