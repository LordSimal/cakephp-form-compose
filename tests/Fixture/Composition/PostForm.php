<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Adjusts an already configured child without repeating its construction or hook.
 */
#[Form('composed-post')]
class PostForm extends FormBlueprint
{
    /**
     * Share the same injected service as nested authors.
     */
    public function __construct(protected readonly CompositionProbe $probe)
    {
    }

    /**
     * Override nested fields after the child has applied its own configuration.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->presentation()->partTemplate('control', 'composition/post');
        $form->add('title');
        $form->embed('author', 'composed-author');
        $form->add('body')->type('textarea');
        $this->probe->record('post', $context);
        $form->field('author.first_name')->label('Author name from parent');
        if ($context->request->getAttribute('stripParentEmail') === true) {
            $form->remove('author.email');
        }
    }
}
