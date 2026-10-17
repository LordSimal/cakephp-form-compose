<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Cake\AttributeResolver\AttributeCollection;
use Cake\AttributeResolver\AttributeResolver;
use Cake\AttributeResolver\Enum\AttributeTargetType;
use Cake\Container\ContainerInterface;
use FormCompose\Attribute\AsFieldType;
use FormCompose\Attribute\Form;
use FormCompose\Form\Field\CompositeType;
use FormCompose\Form\Field\FieldTypeInterface;
use FormCompose\Form\Field\LazyFieldType;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;

/**
 * Discovers named extensions and compiles blueprints for independent form bindings.
 *
 * CakePHP owns discovery and its static metadata cache. Known blueprint classes
 * are reflected directly, so they also work outside configured discovery paths.
 * Only this construction boundary uses the optional application container; form
 * classes receive ordinary constructor dependencies. No bound configuration is cached.
 * Override createCompiler(), createInstance(), or attributes() to specialize construction.
 */
class BlueprintFactory
{
    /**
     * Select application services and a named CakePHP resolver configuration.
     *
     * Without a container, dependency-free blueprints and field types still work.
     * Configure helper option blueprintFactory with the application service when
     * constructors need dependencies. Resolver paths expand across loaded plugins.
     */
    public function __construct(
        protected readonly ?ContainerInterface $container = null,
        protected readonly string $collection = 'formCompose',
    ) {
    }

    /**
     * Resolve a class or discovery alias into immutable configuration for one binding.
     *
     * Parent declarations precede child declarations. Redeclaring a path replaces
     * its options at the original position; duplicate paths on one class are rejected.
     * The concrete blueprint's hook runs once, using ordinary PHP method inheritance.
     *
     * @throws \InvalidArgumentException For unknown aliases or invalid declarations.
     */
    public function compile(string $blueprint, FormContext $context): FormDefinition
    {
        $class = class_exists($blueprint)
            ? $blueprint
            : $this->aliases(Form::class, FormBlueprint::class)[$blueprint] ?? null;
        if ($class === null) {
            throw new InvalidArgumentException(sprintf('Unknown form blueprint "%s".', $blueprint));
        }

        $class = $this->validateClass($class, FormBlueprint::class);
        $context = $context->enter($class);
        $instance = $this->createInstance($class, FormBlueprint::class);

        return $this->createCompiler()->compile($instance, $context);
    }

    /**
     * Construct discovered controls, allowing explicit instances to win without construction.
     *
     * @param array<string, \FormCompose\Form\Field\FieldTypeInterface> $overrides Explicit per-form types.
     * @return array<string, \FormCompose\Form\Field\FieldTypeInterface>
     */
    public function types(array $overrides = []): array
    {
        $types = $overrides;
        foreach ($this->aliases(AsFieldType::class, FieldTypeInterface::class) as $name => $class) {
            $types[$name] ??= $this->createInstance($class, FieldTypeInterface::class);
        }

        return $types;
    }

    /**
     * Discover type names now and defer construction until rendering or registration.
     *
     * Each binding gets independent proxies. Explicit instances win without invoking
     * the discovered constructor. types() remains available for eager construction.
     *
     * @param array<string, \FormCompose\Form\Field\FieldTypeInterface> $overrides Explicit per-form types.
     * @return array<string, \FormCompose\Form\Field\FieldTypeInterface>
     */
    public function lazyTypes(array $overrides = []): array
    {
        $types = $overrides;
        foreach ($this->aliases(AsFieldType::class, FieldTypeInterface::class) as $name => $class) {
            $types[$name] ??= new LazyFieldType(
                fn(): FieldTypeInterface => $this->createInstance($class, FieldTypeInterface::class),
                is_subclass_of($class, CompositeType::class),
            );
        }

        return $types;
    }

    /**
     * Compile one repeated occurrence through this factory's discovery and DI boundary.
     *
     * @internal Used by the session's repeated-layout bridge.
     */
    public function repeated(RepeatedDefinition $entry, FormContext $context): RepeatedDefinition
    {
        return $this->createCompiler()->repeated($entry, $context);
    }

    /**
     * Read only class-target declarations from CakePHP's cached metadata collection.
     */
    protected function attributes(): AttributeCollection
    {
        return AttributeResolver::collection($this->collection)->withTargetType(AttributeTargetType::CLASS_);
    }

    /**
     * Index aliases deterministically and reject collisions instead of silently choosing a class.
     *
     * @param class-string<\FormCompose\Attribute\Form>|class-string<\FormCompose\Attribute\AsFieldType> $attribute Attribute type.
     * @param class-string $expectedClass Required base class or interface.
     * @return array<string, class-string>
     */
    protected function aliases(string $attribute, string $expectedClass): array
    {
        $aliases = [];
        foreach ($this->attributes()->withAttribute($attribute) as $info) {
            $declaration = $info->getInstance($attribute);
            $class = $this->validateClass($info->className, $expectedClass);
            if (isset($aliases[$declaration->name])) {
                throw new InvalidArgumentException(sprintf('Duplicate discovery alias "%s".', $declaration->name));
            }

            $aliases[$declaration->name] = $class;
        }

        return $aliases;
    }

    /**
     * Create the declaration compiler; override to specialize configuration assembly.
     */
    protected function createCompiler(): BlueprintCompiler
    {
        return new BlueprintCompiler($this->compile(...));
    }

    /**
     * Construct a fresh instance and check application container overrides.
     *
     * @template T of object
     * @param class-string $class Class to construct.
     * @param class-string<T> $expectedClass Required contract.
     * @return T
     */
    protected function createInstance(string $class, string $expectedClass): object
    {
        if (
            !$this->container instanceof ContainerInterface
            && (new ReflectionClass($class)->getConstructor()?->getNumberOfRequiredParameters() ?? 0) > 0
        ) {
            throw new LogicException(sprintf(
                'Configure blueprintFactory with the application container to construct "%s".',
                $class,
            ));
        }

        /** @var mixed $instance */
        $instance = $this->container instanceof ContainerInterface
            ? $this->container->getNew($class)
            : new ReflectionClass($class)->newInstance();
        if (!$instance instanceof $expectedClass) {
            throw new InvalidArgumentException(sprintf(
                'Factory for "%s" must return an instance of "%s".',
                $class,
                $expectedClass,
            ));
        }

        return $instance;
    }

    /**
     * Reject unloaded, abstract, or unrelated extension classes before construction.
     *
     * @param class-string $expectedClass Required base class or interface.
     * @return class-string
     */
    protected function validateClass(string $class, string $expectedClass): string
    {
        if (
            !class_exists($class)
            || !new ReflectionClass($class)->isSubclassOf($expectedClass)
            || !new ReflectionClass($class)->isInstantiable()
        ) {
            throw new InvalidArgumentException(sprintf(
                '"%s" must be a concrete implementation of "%s".',
                $class,
                $expectedClass,
            ));
        }

        return $class;
    }
}
