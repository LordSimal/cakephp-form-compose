<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Authoring\DetailsForm;

/**
 * Creates independent author mounts dynamically, including an indexed child.
 */
#[Form('dynamic-post')]
class DynamicPostForm extends FormBlueprint
{
    /**
     * Exercise scoped blueprint resolution and runtime field construction.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title');
        $form->embed('author', AuthorForm::class);
        $form->embed('comments.0.author', 'composed-author');
        $form->embed('details', DetailsForm::class);
        $form->add('body')->type('textarea');
        $form->templateData(['source' => 'builder']);
    }
}
