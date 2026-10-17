<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Embed;
use FormCompose\Blueprint\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Embed('details', DetailsFieldsForm::class)]
#[Embed('comments.0', CommentFieldsForm::class)]
class ScopedArticleForm extends FormBlueprint
{
}
