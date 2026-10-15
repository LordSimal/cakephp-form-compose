<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Attribute\Embed;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\PostForm;

/**
 * Attempts to replace an inherited field with an incompatible embed.
 */
#[Embed('title', AuthorForm::class)]
class ChangedEntryKind extends PostForm
{
}
