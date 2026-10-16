<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\Test\Fixture\Authoring\DetailsForm;

/**
 * Creates independent author mounts dynamically, including an indexed child.
 */
#[Form('dynamic-post')]
#[Field('title')]
class DynamicPostForm extends FormBlueprint
{
    /**
     * Exercise scoped blueprint resolution and runtime field construction.
     */
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->embed('author', AuthorForm::class);
        $form->embed('comments.0.author', 'composed-author');
        $form->embed('details', DetailsForm::class);
        $form->add(
            'body',
            ['type' => 'textarea'],
        );
        $form->presentation(['templateData' => ['source' => 'builder']]);
    }
}
