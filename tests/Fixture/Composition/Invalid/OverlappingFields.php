<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Test\Fixture\Composition\AuthorForm;

/**
 * Collides with a fully qualified leaf from an embedded definition.
 */
#[Field('author.email')]
#[Embed('author', AuthorForm::class)]
class OverlappingFields extends FormBlueprint
{
}
