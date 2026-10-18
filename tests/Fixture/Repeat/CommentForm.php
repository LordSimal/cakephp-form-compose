<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Records per-row hook calls and uses relative source-aware values.
 */
#[Form('repeat-comment')]
class CommentForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->partTemplate('control', 'repeat/comment');
        $form->add('id')->type('hidden');
        $form->add('body')->type('textarea')->help('Comment text');
        /** @var \ArrayObject<int, string>|null $calls */
        $calls = $context->request->getAttribute('repeatCalls');
        $calls?->append($context->path);
        $form->field('body')->label('Comment ' . (string)$context->value('id'));
        if ($context->value('private') === true) {
            $form->remove('body');
        }
    }
}
