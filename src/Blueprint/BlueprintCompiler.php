<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Attribute\Embed;
use FormCompose\Attribute\EmbedMany;
use FormCompose\BlueprintBuilder;
use FormCompose\EmbeddedDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use FormCompose\RepeatedDefinition;

/**
 * Compiles inherited declarations and nested blueprints before the parent's hook.
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
     * @param \Closure(string, \FormCompose\FormContext): \FormCompose\FormDefinition $resolver Scoped blueprint construction.
     */
    public function __construct(protected readonly Closure $resolver)
    {
    }

    /**
     * Compile parent-to-child declarations, then configure this occurrence once.
     *
     * Cloning the blueprint seed isolates object-valued container registrations.
     * Inherited fields merge options in place; redeclared embeds replace their
     * child definition in place. Same-level duplicate paths remain errors.
     */
    public function compile(FormBlueprint $blueprint, FormContext $context): FormDefinition
    {
        $blueprint = clone $blueprint;
        $declarations = BlueprintDeclarations::read($blueprint);
        $entries = [];
        foreach ($declarations['entries'] as $entry) {
            $entries[] = match (true) {
                $entry instanceof Embed => new EmbeddedDefinition(
                    $entry->path,
                    $this->resolve($entry->blueprint, $context->at($entry->path)),
                    $entry->options,
                ),
                $entry instanceof EmbedMany => $this->repeated(new RepeatedDefinition(
                    $entry->path,
                    $entry->blueprint,
                    $entry->keys,
                    $entry->options,
                ), $context),
                default => $entry,
            };
        }

        $builder = $this->createBuilder(new FormDefinition($entries, $declarations['options']), $context);
        $blueprint->configure($builder, $context);

        return $builder->build();
    }

    /**
     * Compile only the requested repeated occurrence before parent configuration.
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
