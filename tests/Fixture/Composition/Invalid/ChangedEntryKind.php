<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\PostForm;

/**
 * Attempts to replace an inherited field with an incompatible embed.
 */
class ChangedEntryKind extends PostForm
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        parent::configure($form, $context);
        $form->embed('title', AuthorForm::class);
    }
}
