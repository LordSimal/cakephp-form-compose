<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Test\Fixture\Composition\AuthorForm;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Field('before')]
#[Embed('author', AuthorForm::class)]
#[Field('after')]
class OrderedAuthorForm extends FormBlueprint
{
}
