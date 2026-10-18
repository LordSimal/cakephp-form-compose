<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Repeat;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Exercises cycle detection at a real requested repeated occurrence.
 */
class CycleForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embedMany('children', CycleForm::class)->keys([0]);
    }
}
