<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Overrides compiled rows after all child hooks have run.
 */
#[Form('repeat-post')]
class PostForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title');
        $form->embedMany('comments', CommentForm::class)->partTemplate('control', 'repeat/comments');
        if ($context->request->getAttribute('removeRow') === true) {
            $form->remove('comments.2');
        }

        if ($context->request->getAttribute('overrideBody') === true) {
            $form->field('comments.0.body')->label('Parent override');
        }
    }
}
