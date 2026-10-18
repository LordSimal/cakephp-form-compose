<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use function Cake\I18n\__d;

/**
 * Defers translated attribute text until a field is resolved in the current locale.
 */
class ArticleForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title')
            ->label(static fn(): string => __d('form-compose-tests', 'Article title'))
            ->help(static fn(): string => __d('form-compose-tests', 'Shown publicly'));
    }
}
