<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintCompiler;
use FormCompose\Blueprint\RepeatedCompiler;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;

/**
 * Demonstrates replacing compiler collaborators without changing the binding API.
 */
class CustomBlueprintCompiler extends BlueprintCompiler
{
    /**
     * Extend the collection through the protected builder factory.
     */
    protected function createBuilder(FormDefinition $definition, FormContext $context): FormBuilder
    {
        return new CustomFormBuilder(
            $definition->add(new FieldDefinition('custom', ['label' => 'Custom builder'])),
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
