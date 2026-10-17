<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Embed;
use FormCompose\Attribute\EmbedMany;
use FormCompose\Attribute\Form;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Exercises alias selection through named attribute and builder arguments.
 */
#[Form('named-parent')]
#[Embed(path: 'author', blueprint: 'profile')]
#[EmbedMany(path: 'reviewers', blueprint: 'profile', keys: [0])]
class NamedParentForm extends FormBlueprint
{
    /**
     * Compose runtime children through the same named blueprint contract.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embed(path: 'editor', blueprint: 'profile');
        $form->embedMany(path: 'contributors', blueprint: 'profile', keys: [1]);
    }
}
