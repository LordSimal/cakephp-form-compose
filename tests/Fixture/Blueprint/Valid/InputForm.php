<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Supplies native choice, file and disabled configuration through class declarations.
 */
class InputForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('tags')->choices(['a' => '<Alpha>', 'b' => 'Beta'])->multiple(true);
        $form->add('status')->choices(['a' => '<Active>', 'b' => 'Blocked'])->type('radio')->help('Choose <one>');
        $form->add('attachment')->value('/private/path.txt')->type('file');
        $form->add('disabled')->disabled(true)->value('Unsubmitted');
    }
}
