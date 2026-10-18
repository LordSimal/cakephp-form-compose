<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;

/** Configures one session field and resolves independent state without rendering. */
class FieldBuilder extends FieldConfigBuilder
{
    /** @param \Closure(FieldConfig): BoundField $resolve Field resolution through the owning session. */
    public function __construct(
        protected readonly Closure $resolve,
        FieldConfig|FieldConfigBuilder $config = new FieldConfig(),
    ) {
        parent::__construct($config);
    }

    /** Resolve state without registering input names, IDs, feedback, or security data. */
    public function resolve(): BoundField
    {
        return ($this->resolve)($this->config());
    }
}
