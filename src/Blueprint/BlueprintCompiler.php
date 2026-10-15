<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use ReflectionClass;

/**
 * Combines inherited static declarations and one blueprint's runtime configuration.
 *
 * Each compilation starts with a fresh builder; snapshots and request-dependent
 * choices never enter the discovery cache. Override createBuilder() to supply
 * specialized configuration operations through BlueprintFactory::createCompiler().
 */
class BlueprintCompiler
{
    /**
     * Read declarations from parent to child, then invoke the concrete hook once.
     *
     * Clone the blueprint seed so object-valued DI registrations remain reusable.
     * Constructor dependencies are shared by reference, using normal PHP cloning.
     * Child options override matching parent keys without moving the field.
     * Duplicate declarations on the same class fail through FormDefinition::add().
     */
    public function compile(FormBlueprint $blueprint, FormContext $context): FormDefinition
    {
        $blueprint = clone $blueprint;
        $reflection = new ReflectionClass($blueprint);
        $hierarchy = [$reflection];
        while (($parent = $reflection->getParentClass()) !== false) {
            array_unshift($hierarchy, $parent);
            $reflection = $parent;
        }
        $definition = FormDefinition::make();
        foreach ($hierarchy as $level) {
            $declared = FormDefinition::make();
            foreach ($level->getAttributes(Field::class) as $attribute) {
                $declared = $declared->add($attribute->newInstance()->definition());
            }
            foreach ($declared->fields as $field) {
                $definition = $definition->has($field->path)
                    ? $definition->with($field->path, $field->options)
                    : $definition->add($field);
            }
        }
        $builder = $this->createBuilder($definition);
        $blueprint->configure($builder, $context);

        return $builder->build();
    }

    /**
     * Supply the per-binding builder used by the public configure() hook.
     */
    protected function createBuilder(FormDefinition $definition): FormBuilder
    {
        return new FormBuilder($definition);
    }
}
