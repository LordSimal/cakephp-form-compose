<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Embed;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Test\Fixture\Blueprint\Valid\InputForm;

/**
 * Supplies a minimal blueprint for application authoring integration tests.
 */
#[Embed('author', IdForm::class)]
#[Embed('inputs', InputForm::class)]
class SecureForm extends FormBlueprint
{
}
