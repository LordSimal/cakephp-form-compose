<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Supplies static configuration and explicit false/null/zero values for binding tests.
 */
#[Form('profile')]
class ProfileForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('name')->label('Full <name>');
        $form->add('email')
            ->attributes(['class' => 'original'])
            ->type('email')
            ->label('Email')
            ->help('Use <work> email.');
        $form->add('comments.0.body')->value(null);
        $form->add('active')->value(false)->type('checkbox')->label(false);
        $form->add('count')->value(0);
    }
}
