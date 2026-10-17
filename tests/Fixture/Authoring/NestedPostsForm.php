<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\EmbedMany;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[EmbedMany('posts', CommentsForm::class)]
class NestedPostsForm extends FormBlueprint
{
}
