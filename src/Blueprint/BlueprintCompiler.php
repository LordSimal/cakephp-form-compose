<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Attribute\Embed;
use FormCompose\EmbeddedDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;
use FormCompose\FormDefinition;
use LogicException;

/**
 * Compiles inherited declarations and nested blueprints before the parent's hook.
 *
 * Request-dependent definitions never enter discovery caches. Override
 * createBuilder() through BlueprintFactory::createCompiler() to specialize
 * operations; resolver injection preserves application compiler/builder subclasses.
 */
class BlueprintCompiler
{
    /** @var \Closure(string, \FormCompose\FormContext): \FormCompose\FormDefinition|null Blueprint construction. */
    protected ?Closure $resolver = null;

    /**
     * Supply the owning factory's resolver without mutable shared compilation state.
     *
     * @param \Closure(string, \FormCompose\FormContext): \FormCompose\FormDefinition $resolver Scoped construction.
     */
    public function withResolver(Closure $resolver): static
    {
        $compiler = clone $this;
        $compiler->resolver = $resolver;

        return $compiler;
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
            $entries[] = $entry instanceof Embed
                ? new EmbeddedDefinition($entry->path, $this->resolve($entry->definition, $context->at($entry->path)))
                : $entry;
        }

        $definition = new FormDefinition($entries, $declarations['options']);
        $builder = $this->createBuilder($definition)->withResolver(
            fn(string $path, string $name): FormDefinition => $this->resolve($name, $context->at($path)),
        );
        $blueprint->configure($builder, $context);

        return $builder->build();
    }

    /**
     * Resolve a child through the current factory, retaining DI and cycle tracking.
     */
    protected function resolve(string $name, FormContext $context): FormDefinition
    {
        if (!$this->resolver instanceof Closure) {
            throw new LogicException('Blueprint embedding requires a compiler supplied by BlueprintFactory.');
        }

        return ($this->resolver)($name, $context);
    }

    /**
     * Supply the per-resolution builder used by the public configure() hook.
     */
    protected function createBuilder(FormDefinition $definition): FormBuilder
    {
        return new FormBuilder($definition);
    }
}
