<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\EmbedMany;
use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Overrides compiled rows after all child hooks have run.
 */
#[Form('repeat-post')]
#[Field('title')]
#[EmbedMany('comments', CommentForm::class, options: ['template' => 'repeat/comments'])]
class PostForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        if ($context->request->getAttribute('removeRow') === true) {
            $form->remove('comments.2');
        }

        if ($context->request->getAttribute('overrideBody') === true) {
            $form->with('comments.0.body', ['label' => 'Parent override']);
        }
    }
}
