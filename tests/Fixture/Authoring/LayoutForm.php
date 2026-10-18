<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/** Exercises complete-form layouts with locked data, feedback, and uploads. */
class LayoutForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('id')->type('hidden');
        $form->add('title');
        $form->add('attachment')->type('file');
    }
}
