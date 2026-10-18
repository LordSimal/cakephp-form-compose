<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Composition\AuthorForm;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
class OrderedAuthorForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('before');
        $form->embed('author', AuthorForm::class);
        $form->add('after');
    }
}
