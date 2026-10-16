<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\Test\Fixture\Authoring\RuntimePostForm;

/**
 * Adds rows dynamically and modifies their compiled fields in the same hook.
 */
class RuntimeForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->embedMany('comments', CommentForm::class, keys: [0, 3]);
        $form->with('comments.3.body', ['label' => 'New comment']);
        $form->remove('comments.0.id');

        $form->embed('post', RuntimePostForm::class);
        $form->with('post.comments.0.body', ['label' => 'Nested blank']);
    }
}
