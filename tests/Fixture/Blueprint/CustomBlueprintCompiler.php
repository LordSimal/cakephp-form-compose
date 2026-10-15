<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintCompiler;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBuilder;
use FormCompose\FormDefinition;

/**
 * Demonstrates replacing compiler collaborators without changing the binding API.
 */
class CustomBlueprintCompiler extends BlueprintCompiler
{
    /**
     * Extend the collection through the protected builder factory.
     */
    protected function createBuilder(FormDefinition $definition): FormBuilder
    {
        return new FormBuilder($definition->add(FieldDefinition::make('custom')->label('Custom builder')));
    }
}
