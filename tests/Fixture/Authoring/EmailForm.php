<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Supplies a minimal blueprint for application authoring integration tests.
 */
#[Field('email', type: 'email', label: 'Default email')]
class EmailForm extends FormBlueprint
{
}
