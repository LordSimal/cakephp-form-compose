<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

use Closure;
use FormCompose\Builder\EmbeddedBlueprintBuilder;
use FormCompose\Builder\RepeatedBlueprintBuilder;

/**
 * Authors child occurrences through protected factories while deferring their hooks.
 * @require-extends \FormCompose\Blueprint\BlueprintBuilder
 */
trait ChildDeclarationsTrait
{
    /** Declare a child blueprint and configure its occurrence presentation. */
    public function embed(string $path, string $blueprint): EmbeddedBlueprintBuilder
    {
        $this->assertAvailable($path);

        return $this->entries[$path] = $this->createEmbedBuilder($path, fn(): FormDefinition => ($this->resolver)(
            $path,
            $blueprint,
        ));
    }

    /** Declare repeated rows; keys() is evaluated before any row hooks run. */
    public function embedMany(string $path, string $blueprint): RepeatedBlueprintBuilder
    {
        $this->assertAvailable($path);

        return $this->entries[$path] = $this->createRepeatedBuilder($path, function (?array $keys) use (
            $path,
            $blueprint,
        ): RepeatedDefinition {
            /** @var list<int|string>|null $keys */
            $declaration = new RepeatedDefinition($path, $blueprint, $keys);

            return ($this->repeated)($declaration);
        });
    }

    /** @param \Closure(): FormDefinition $compile Scoped child compilation boundary. */
    protected function createEmbedBuilder(string $path, Closure $compile): EmbeddedBlueprintBuilder
    {
        return new EmbeddedBlueprintBuilder($path, $compile);
    }

    /** @param \Closure(list<int|string>|null): RepeatedDefinition $compile Scoped row compilation boundary. */
    protected function createRepeatedBuilder(string $path, Closure $compile): RepeatedBlueprintBuilder
    {
        return new RepeatedBlueprintBuilder($path, $compile);
    }
}
