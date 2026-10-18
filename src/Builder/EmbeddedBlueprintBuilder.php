<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Blueprint\EmbeddedDefinition;
use FormCompose\Blueprint\FormDefinition;

/** Stages one child blueprint and its layout presentation without row-only settings. */
class EmbeddedBlueprintBuilder extends PresentationBuilder
{
    protected ?FormDefinition $compiled = null;

    /** @param \Closure(): FormDefinition $compile Compile this child in its scoped context. */
    public function __construct(protected readonly string $path, protected readonly Closure $compile)
    {
        parent::__construct();
    }

    /** Compile once; later presentation edits capture new occurrence metadata only. */
    public function build(): EmbeddedDefinition
    {
        return new EmbeddedDefinition($this->path, $this->compiled ??= ($this->compile)(), $this->config());
    }
}
