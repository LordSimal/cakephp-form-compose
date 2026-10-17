<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintCompiler;
use FormCompose\Blueprint\BlueprintFactory;

/**
 * Selects application compilation behavior through the factory extension point.
 */
class CustomBlueprintFactory extends BlueprintFactory
{
    /**
     * Supply the application's compiler using its ordinary service subclass.
     */
    protected function createCompiler(): BlueprintCompiler
    {
        return new CustomBlueprintCompiler($this->compile(...));
    }
}
