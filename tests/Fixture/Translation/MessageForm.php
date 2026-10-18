<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__d;
use function Cake\I18n\__dx;

/**
 * Translates blueprint text through CakePHP during configuration.
 */
class MessageForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title')
            ->label(__dx('form-compose-tests', 'heading', 'Article title {0}', '<public>'))
            ->help(__d('form-compose-tests', 'Shown publicly'));
    }
}
