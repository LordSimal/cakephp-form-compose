<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Supplies relative fields and a custom layout for each author occurrence.
 */
#[Form('composed-author')]
class AuthorForm extends FormBlueprint
{
    protected int $calls = 0;

    /**
     * Share a real application service, constructed by the container.
     */
    public function __construct(protected readonly CompositionProbe $probe)
    {
    }

    /**
     * Record scoped data and remove optional controls before a parent hook runs.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->presentation()->partTemplate('control', 'composition/author')->templateData(['heading' => '<Author>']);
        $form->add('first_name')->label('First <name>')->help('Use <given> name.');
        $form->add('last_name');
        $form->add('email')
            ->type('email')
            ->help('Work address')
            ->partTemplate('control', 'themes/custom/control')
            ->partTemplate('help', 'composition/help')
            ->templateData(['message' => '<Work> email']);
        $form->add('internal');
        $form->add('id')->value('7')->type('hidden');
        $this->probe->record('author', $context);
        $form->remove('internal');
        $form->field('last_name')->label((string)($context->context->val('label') ?? 'Surname'));
        $form->field('first_name')->templateData(['calls' => ++$this->calls]);
        if ($context->request->getAttribute('canEditEmail') === false) {
            $form->remove('email');
        }
    }
}
