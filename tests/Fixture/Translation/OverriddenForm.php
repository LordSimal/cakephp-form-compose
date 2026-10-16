<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use RuntimeException;

/**
 * Proves discarded deferred defaults never execute during blueprint binding.
 */
#[Field(
    'title',
    label: static function (): never {
        throw new RuntimeException('Discarded label.');
    },
    help: static function (): never {
        throw new RuntimeException('Discarded help.');
    },
)]
class OverriddenForm extends FormBlueprint
{
}
