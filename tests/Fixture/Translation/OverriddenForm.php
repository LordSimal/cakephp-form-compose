<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use RuntimeException;

/**
 * Proves discarded deferred defaults never execute during blueprint binding.
 */
class OverriddenForm extends FormBlueprint
{
    /** Declare reusable fields through the fluent blueprint API. */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('title')
            ->label(static function (): never {
                throw new RuntimeException('Discarded label.');
            })
            ->help(static function (): never {
                throw new RuntimeException('Discarded help.');
            });
    }
}
