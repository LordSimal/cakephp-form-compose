<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\FormSession;
use FormCompose\Rendering\RenderableInterface;

/** Configures a whole control; rendering resolves its final field configuration. */
class ControlBuilder extends FieldBuilder implements RenderableInterface
{
    /** @param \Closure(FieldConfig): \FormCompose\Form\Field\BoundField $resolve Owning field resolver. */
    public function __construct(
        Closure $resolve,
        FieldConfig|FieldConfigBuilder $config,
        public readonly FormSession $owner,
    ) {
        parent::__construct($resolve, $config);
    }

    /** Resolve and render a complete field through the normal registration transaction. */
    public function render(): string
    {
        return $this->resolve()->control();
    }

    /** Produce the same markup and side effects as explicit rendering. */
    public function __toString(): string
    {
        return $this->render();
    }
}
