<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;
use FormCompose\Binding\FormBinding;
use FormCompose\Binding\PathValue;
use FormCompose\Binding\ScopedContext;
use FormCompose\Binding\ValueResolver;
use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Supplies one binding's data metadata and request to blueprint configuration.
 *
 * The context can inspect values with context->val('field') and request attributes
 * without access to rendering or submission-registration services. It is never cached.
 */
final readonly class FormContext
{
    /**
     * Capture the same context and request used by the resulting form session.
     *
     * @param list<class-string> $ancestors Blueprint resolution stack, never discovery metadata.
     * @param list<string> $valueSources Binding value-source order.
     */
    public function __construct(
        public ContextInterface $context,
        public ServerRequest $request,
        public array $ancestors = [],
        public string $path = '',
        public array $valueSources = ['data', 'context'],
    ) {
    }

    /**
     * Scope field lookups to a relative mount while retaining request and cycle tracking.
     */
    public function at(string $path): self
    {
        new FieldDefinition($path);

        return new self(
            new ScopedContext($this->context, $path),
            $this->request,
            $this->ancestors,
            $this->path === '' ? $path : $this->path . '.' . $path,
            $this->valueSources,
        );
    }

    /**
     * Enter one blueprint occurrence, rejecting recursive composition before its hook.
     *
     * @param class-string $class Resolved blueprint class.
     */
    public function enter(string $class): self
    {
        if (in_array($class, $this->ancestors, true)) {
            throw new InvalidArgumentException(
                'Circular blueprint embedding: ' . implode(' -> ', [...$this->ancestors, $class]),
            );
        }

        return new self(
            $this->context,
            $this->request,
            [...$this->ancestors, $class],
            $this->path,
            $this->valueSources,
        );
    }

    /**
     * Resolve a relative value using the binding's source order without changing the public request.
     */
    public function value(string $path): mixed
    {
        $request = $this->request;
        if ($this->path !== '') {
            /** @var mixed $body */
            $body = $request->getData();
            /** @var mixed $data */
            $data = PathValue::lookup(is_array($body) ? $body : [], $this->path)->value;
            /** @var mixed $query */
            $query = PathValue::lookup($request->getQueryParams(), $this->path)->value;
            $request = $request->withParsedBody(is_array($data) ? $data : [])
                ->withQueryParams(is_array($query) ? $query : []);
        }

        return new ValueResolver(new FormBinding($this->context, $request, '', [
            'valueSources' => $this->valueSources,
        ]))->resolve($path, []);
    }

    /**
     * Preserve one binding's context, request, and source ordering for scoped compilation.
     */
    public static function fromBinding(FormBinding $binding): self
    {
        /** @var list<string> $sources */
        $sources = $binding->options['valueSources'] ?? ['data', 'context'];

        return new self($binding->context, $binding->request, valueSources: $sources);
    }
}
