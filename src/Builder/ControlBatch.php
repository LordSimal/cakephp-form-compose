<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Form\Field\ControlList;
use FormCompose\Form\Field\FieldSpec;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\RenderableInterface;
use InvalidArgumentException;

/** Validates and renders an ordered batch through its owning form's transaction. */
class ControlBatch implements RenderableInterface
{
    /**
     * @param iterable<array-key, mixed> $fields Paths, specs, or configured controls.
     * @param \Closure(FieldSpec): string $control Scoped control rendering.
     * @param \Closure(\Closure(): string): string|null $transaction Registration transaction for the whole batch.
     */
    public function __construct(
        protected readonly iterable $fields,
        protected readonly Closure $control,
        protected readonly FormSession $owner,
        protected readonly ?Closure $transaction = null,
    ) {
    }

    /** Validate the complete batch before rendering, then commit it as one operation. */
    public function render(): string
    {
        $fields = ControlList::normalize($this->fields);
        foreach ($fields as $field) {
            if ($field instanceof ControlBuilder && $field->owner !== $this->owner) {
                throw new InvalidArgumentException('Batch controls must belong to this form session.');
            }
        }

        $render = function () use ($fields): string {
            $html = '';
            foreach ($fields as $field) {
                $html .= $field instanceof ControlBuilder ? $field->render() : ($this->control)($field);
            }

            return $html;
        };

        return $this->transaction instanceof Closure ? ($this->transaction)($render) : $render();
    }

    /** Echo uses the same transaction as explicit rendering. */
    public function __toString(): string
    {
        return $this->render();
    }
}
