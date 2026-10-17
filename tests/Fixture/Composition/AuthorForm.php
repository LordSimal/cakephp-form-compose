<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\BlueprintBuilder;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;

/**
 * Supplies relative fields and a custom layout for each author occurrence.
 */
#[Form('composed-author', template: 'composition/author', templateData: ['heading' => '<Author>'])]
#[Field('first_name', label: 'First <name>', help: 'Use <given> name.')]
#[Field('last_name')]
#[Field(
    'email',
    type: 'email',
    help: 'Work address',
    template: 'themes/custom/control',
    templates: ['help' => 'composition/help'],
    templateData: ['message' => '<Work> email'],
)]
#[Field('internal')]
#[Field('id', type: 'hidden', options: ['value' => '7'])]
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
        $this->probe->record('author', $context);
        $form->remove('internal');
        $form->with('last_name', ['label' => $context->context->val('label') ?? 'Surname']);
        $form->with('first_name', ['templateData' => ['calls' => ++$this->calls]]);
        if ($context->request->getAttribute('canEditEmail') === false) {
            $form->remove('email');
        }
    }
}
