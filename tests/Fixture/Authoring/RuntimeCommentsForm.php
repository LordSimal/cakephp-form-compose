<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\BlueprintBuilder;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;

/**
 * Exercises inferred row compilation through the runtime builder.
 */
class RuntimeCommentsForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embedMany('comments', BodyForm::class);
    }
}
