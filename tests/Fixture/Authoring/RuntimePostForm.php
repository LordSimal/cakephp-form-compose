<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\EmbedMany;
use FormCompose\FormBlueprint;

/**
 * Supplies a minimal blueprint for application authoring integration tests.
 */
#[EmbedMany('comments', BodyForm::class, keys: [0])]
class RuntimePostForm extends FormBlueprint
{
}
