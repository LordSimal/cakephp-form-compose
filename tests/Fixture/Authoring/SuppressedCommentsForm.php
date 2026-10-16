<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\EmbedMany;
use FormCompose\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[EmbedMany('comments', EmptyForm::class, options: ['errors' => false])]
class SuppressedCommentsForm extends FormBlueprint
{
}
