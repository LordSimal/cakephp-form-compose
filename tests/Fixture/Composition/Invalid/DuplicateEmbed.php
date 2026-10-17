<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Attribute\Embed;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Test\Fixture\Composition\AuthorForm;

/**
 * Declares the same mount twice at one inheritance level.
 */
#[Embed('author', AuthorForm::class)]
#[Embed('author', AuthorForm::class)]
class DuplicateEmbed extends FormBlueprint
{
}
