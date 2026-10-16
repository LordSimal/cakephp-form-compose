<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\Test\Fixture\Composition\PostForm;

/**
 * Exercises rollback with a failing layout selected through blueprint configuration.
 */
class FailingPostForm extends PostForm
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        parent::configure($form, $context);
        $form->presentation(['template' => 'composition/failure']);
    }
}
