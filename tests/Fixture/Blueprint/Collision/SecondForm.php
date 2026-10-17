<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Collision;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Second half of a discovery collision across different source files.
 */
#[Form('collision')]
class SecondForm extends FormBlueprint
{
}
