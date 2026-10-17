<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\EmbedMany;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Exercises cycle detection at a real requested repeated occurrence.
 */
#[EmbedMany('children', CycleForm::class, keys: [0])]
class CycleForm extends FormBlueprint
{
}
