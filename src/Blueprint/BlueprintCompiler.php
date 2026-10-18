<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;

/**
 * Runs blueprint authoring, then compiles its final child declarations.
 *
 * Request-dependent definitions never enter discovery caches. Override
 * createBuilder() through BlueprintFactory::createCompiler() to specialize
 * operations; resolver injection preserves application compiler/builder subclasses.
 */
class BlueprintCompiler
{
    /**
     * Resolve child blueprints through the owning factory.
     *
     * @param \Closure(string, \FormCompose\Blueprint\FormContext): \FormCompose\Blueprint\FormDefinition $resolver Scoped blueprint construction.
     */
    public function __construct(protected readonly Closure $resolver)
    {
    }

    /**
     * Configure this occurrence once, then compile its children.
     *
     * Cloning the blueprint seed isolates object-valued container registrations.
     * Inherited hooks use normal parent::configure() calls. Parent field edits
     * overlay compiled child defaults. Duplicate declarations remain errors.
     */
    public function compile(FormBlueprint $blueprint, FormContext $context): FormDefinition
    {
        $blueprint = clone $blueprint;
        $builder = $this->createBuilder(new FormDefinition([]), $context);
        $blueprint->configure($builder, $context);

        return $builder->build();
    }

    /**
     * Compile only the repeated rows selected during parent configuration.
     */
    public function repeated(RepeatedDefinition $entry, FormContext $context): RepeatedDefinition
    {
        return $this->createRepeatedCompiler()->compile($entry, $context);
    }

    /**
     * Supply row compilation and key inference through the compiler extension point.
     */
    protected function createRepeatedCompiler(): RepeatedCompiler
    {
        return new RepeatedCompiler($this->resolve(...));
    }

    /**
     * Resolve a child through the current factory, retaining DI and cycle tracking.
     */
    protected function resolve(string $name, FormContext $context): FormDefinition
    {
        return ($this->resolver)($name, $context);
    }

    /**
     * Supply the per-resolution builder used by the public configure() hook.
     */
    protected function createBuilder(FormDefinition $definition, FormContext $context): BlueprintBuilder
    {
        return new BlueprintBuilder(
            $definition,
            fn(string $path, string $name): FormDefinition => $this->resolve($name, $context->at($path)),
            fn(RepeatedDefinition $entry): RepeatedDefinition => $this->repeated($entry, $context),
        );
    }
}
