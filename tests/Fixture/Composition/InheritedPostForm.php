<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormContext;

/**
 * Redeclares a child and a leaf while retaining inherited declaration positions.
 */
#[Form('inherited-post')]
class InheritedPostForm extends PostForm
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        parent::configure($form, $context);
        $form->templateData(['inherited' => true]);
        $form->field('title')->label('Inherited title');
        $form->add('extra');
    }
}
