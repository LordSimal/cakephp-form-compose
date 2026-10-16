<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\Embed;
use FormCompose\FormBlueprint;

/**
 * Verifies relative repetition and occurrence wrappers inside an ordinary embed.
 */
#[Embed('post', PostForm::class, options: ['template' => 'repeat/post-wrapper'])]
class NestedForm extends FormBlueprint
{
}
