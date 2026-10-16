<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\Translated;
use RuntimeException;

/**
 * Extraction must inspect static declarations without compiling or evaluating text.
 */
#[Field(
    'title',
    label: new Translated('Catalog title', domain: 'blueprints', context: 'heading'),
    options: [
        'label' => new Translated('Discarded title'),
        'help' => new Translated('Catalog help'),
    ],
)]
#[Field('ignored', label: static function (): never {
    throw new RuntimeException('Do not execute text callbacks during extraction.');
})]
class ExtractionForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        throw new RuntimeException('Do not execute configure() during extraction.');
    }
}
