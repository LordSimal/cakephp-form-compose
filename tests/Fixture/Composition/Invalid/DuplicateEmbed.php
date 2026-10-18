<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Composition\AuthorForm;

/**
 * Declares the same mount twice at one inheritance level.
 */
class DuplicateEmbed extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embed('author', AuthorForm::class);
        $form->embed('author', AuthorForm::class);
    }
}
