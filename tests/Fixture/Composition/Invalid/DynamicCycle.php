<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition\Invalid;

use FormCompose\BlueprintBuilder;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;

/**
 * Attempts recursive composition from a runtime hook.
 */
class DynamicCycle extends FormBlueprint
{
    /**
     * Re-enter the same class through the builder's scoped factory.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->embed('loop', self::class);
    }
}
