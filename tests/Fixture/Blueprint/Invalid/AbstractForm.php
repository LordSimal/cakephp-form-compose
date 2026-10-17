<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Invalid;

use FormCompose\Blueprint\FormBlueprint;

/**
 * Rejects non-instantiable blueprints before configuration.
 */
abstract class AbstractForm extends FormBlueprint
{
}
