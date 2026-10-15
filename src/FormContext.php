<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;
use FormCompose\Binding\ScopedContext;
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
     */
    public function __construct(
        public ContextInterface $context,
        public ServerRequest $request,
        public array $ancestors = [],
    ) {
    }

    /**
     * Scope field lookups to a relative mount while retaining request and cycle tracking.
     */
    public function at(string $path): self
    {
        new FieldDefinition($path);

        return new self(new ScopedContext($this->context, $path), $this->request, $this->ancestors);
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

        return new self($this->context, $this->request, [...$this->ancestors, $class]);
    }
}
