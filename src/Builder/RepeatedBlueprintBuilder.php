<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Blueprint\RepeatedDefinition;

/** Stages a repeated child, selecting final keys before compiling any row hooks. */
class RepeatedBlueprintBuilder extends EmbedConfigBuilder
{
    use RowSelectionTrait;

    protected ?RepeatedDefinition $compiled = null;

    /** @var list<int|string>|null Last compiled row selection. */
    protected ?array $compiledKeys = null;

    /** @param \Closure(list<int|string>|null): RepeatedDefinition $compile Compile selected rows in their scoped contexts. */
    public function __construct(protected readonly string $path, protected readonly Closure $compile)
    {
        parent::__construct();
    }

    /** Capture selected rows and current occurrence presentation independently. */
    public function build(): RepeatedDefinition
    {
        $compiled = $this->compiled();

        return new RepeatedDefinition(
            $this->path,
            $compiled->blueprint,
            $compiled->keys,
            $this->config(),
            $compiled->rows,
        );
    }

    /** Reuse compiled rows until the explicit row selection changes. */
    protected function compiled(): RepeatedDefinition
    {
        $keys = $this->selectedKeys;
        if ($this->compiled instanceof RepeatedDefinition && $this->compiledKeys === $keys) {
            return $this->compiled;
        }

        $compiled = ($this->compile)($keys);
        $this->compiledKeys = $keys;

        return $this->compiled = $compiled;
    }
}
