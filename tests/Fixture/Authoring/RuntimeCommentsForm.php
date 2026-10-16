<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

/**
 * Exercises inferred row compilation through the runtime builder.
 */
class RuntimeCommentsForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->embedMany('comments', BodyForm::class);
    }
}
