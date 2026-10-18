<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Form\Field\Translated;

/**
 * Supplies locale-independent attribute metadata with context and arguments.
 */
class MessageForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title')
            ->label(new Translated('Article title {0}', ['<public>'], domain: 'form-compose-tests', context: 'heading'))
            ->help(new Translated('Shown publicly', domain: 'form-compose-tests'));
    }
}
