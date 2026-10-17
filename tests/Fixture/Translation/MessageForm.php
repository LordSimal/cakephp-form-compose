<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Form\Field\Translated;

/**
 * Supplies locale-independent attribute metadata with context and arguments.
 */
#[Field(
    'title',
    label: new Translated('Article title {0}', ['<public>'], domain: 'form-compose-tests', context: 'heading'),
    help: new Translated('Shown publicly', domain: 'form-compose-tests'),
)]
class MessageForm extends FormBlueprint
{
}
