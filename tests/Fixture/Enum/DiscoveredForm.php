<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Leaves enum choice discovery to the bound schema, including scoped occurrences.
 */
class DiscoveredForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('status');
    }
}
