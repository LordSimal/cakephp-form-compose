<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Reads injected choices, bound metadata and per-request conditions without retaining state.
 */
#[Form('conditional')]
class ConditionalForm extends FormBlueprint
{
    protected int $calls = 0;

    /**
     * Use an ordinary constructor dependency resolved by the application container.
     */
    public function __construct(protected readonly ProfileChoices $choices)
    {
    }

    /**
     * Configure an independent collection for each binding.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('email')->type('email')->label('Email');
        $form->add('internal_note');
        $form->add('id')->value('42')->type('hidden');
        $form->remove('internal_note');
        $form->field('email')
            ->label((string)$context->context->val('label'))
            ->templateData(['calls' => ++$this->calls]);
        $form->add('kind')->choices($this->choices->all());
        if ($context->request->getAttribute('canManageAccounts') === true) {
            $form->add('active')->type('checkbox')->label('Account enabled');
        }
    }
}
