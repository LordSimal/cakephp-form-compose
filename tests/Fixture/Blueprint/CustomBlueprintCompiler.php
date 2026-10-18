<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\BlueprintCompiler;
use FormCompose\Blueprint\FormContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedCompiler;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;

/**
 * Demonstrates replacing compiler collaborators without changing the binding API.
 */
class CustomBlueprintCompiler extends BlueprintCompiler
{
    /**
     * Extend the collection through the protected builder factory.
     */
    protected function createBuilder(FormDefinition $definition, FormContext $context): BlueprintBuilder
    {
        return new CustomBlueprintBuilder(
            $definition->add(new FieldDefinition('custom', FieldConfig::builder()->label('Custom builder')->config())),
            fn(string $path, string $name): FormDefinition => $this->resolve($name, $context->at($path)),
            fn(RepeatedDefinition $entry): RepeatedDefinition => $this->repeated($entry, $context),
        );
    }

    /**
     * Supply application row-key inference through the compiler factory.
     */
    protected function createRepeatedCompiler(): RepeatedCompiler
    {
        return new CustomRepeatedCompiler($this->resolve(...));
    }
}
