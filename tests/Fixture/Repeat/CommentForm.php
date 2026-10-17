<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Records per-row hook calls and uses relative source-aware values.
 */
#[Form('repeat-comment', template: 'repeat/comment')]
#[Field('id', type: 'hidden')]
#[Field('body', type: 'textarea', help: 'Comment text')]
class CommentForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
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
