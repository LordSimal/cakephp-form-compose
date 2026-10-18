<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Authoring\RuntimePostForm;

/**
 * Adds rows dynamically and modifies their compiled fields in the same hook.
 */
class RuntimeForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embedMany(path: 'comments', blueprint: CommentForm::class)->keys([0, 3]);
        $form->field('comments.3.body')->label('New comment');
        $form->remove('comments.0.id');

        $form->embed('post', RuntimePostForm::class);
        $form->field('post.comments.0.body')->label('Nested blank');
    }
}
