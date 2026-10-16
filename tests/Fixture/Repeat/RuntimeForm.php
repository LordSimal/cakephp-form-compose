<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;

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

        $child = FormDefinition::make()->add(FieldDefinition::make('body'));
        $form->embed('post', FormDefinition::make()->add(new RepeatedDefinition('comments', $child, [0])));
        $form->with('post.comments.0.body', ['label' => 'Nested blank']);
    }
}
