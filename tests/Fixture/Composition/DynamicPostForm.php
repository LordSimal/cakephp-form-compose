<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

/**
 * Creates independent author mounts dynamically, including an indexed child.
 */
#[Form('dynamic-post')]
#[Field('title')]
class DynamicPostForm extends FormBlueprint
{
    /**
     * Exercise scoped string resolution and prebuilt collection composition.
     */
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->embed('author', AuthorForm::class);
        $form->embed('comments.0.author', 'composed-author');
        $form->embed('details', new FormBuilder()->add(FieldDefinition::make('summary'))->build());
        $form->add(FieldDefinition::make('body', 'textarea'));
        $form->presentation(['templateData' => ['source' => 'builder']]);
    }
}
