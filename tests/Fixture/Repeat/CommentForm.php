<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

/**
 * Records per-row hook calls and uses relative source-aware values.
 */
#[Form('repeat-comment', template: 'repeat/comment')]
#[Field('id', type: 'hidden')]
#[Field('body', type: 'textarea', help: 'Comment text')]
class CommentForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        /** @var \ArrayObject<int, string>|null $calls */
        $calls = $context->request->getAttribute('repeatCalls');
        $calls?->append($context->path);
        $form->with('body', ['label' => 'Comment ' . (string)$context->value('id')]);
        if ($context->value('private') === true) {
            $form->remove('body');
        }
    }
}
