<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Exercises alias selection through named attribute and builder arguments.
 */
#[Form('named-parent')]
class NamedParentForm extends FormBlueprint
{
    /**
     * Compose runtime children through the same named blueprint contract.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embed('author', 'profile');
        $form->embedMany('reviewers', 'profile')->keys([0]);
        $form->embed(path: 'editor', blueprint: 'profile');
        $form->embedMany(path: 'contributors', blueprint: 'profile')->keys([1]);
    }
}
