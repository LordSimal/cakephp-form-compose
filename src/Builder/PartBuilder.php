<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use Closure;
use FormCompose\Configuration\PresentationConfig;
use FormCompose\Rendering\RenderableInterface;

/**
 * Defers rendering of one field part or scoped layout until its presentation is complete.
 * @extends ConfigurationBuilder<PresentationConfig>
 */
class PartBuilder extends ConfigurationBuilder implements RenderableInterface
{
    /** Capture immutable presentation independently of future edits. */
    public function config(): PresentationConfig
    {
        return $this->configuration;
    }

    /** Overlay another presentation layer. */
    public function apply(PresentationConfig $config): static
    {
        $this->configuration = $this->configuration->merge($config);

        return $this;
    }

    /** @param \Closure(PresentationConfig): string $render Rendering boundary. */
    public function __construct(protected readonly Closure $render, string $part = 'control')
    {
        parent::__construct(new PresentationConfig());
        $this->primaryPart = $part;
    }

    /** Render through the owner, preserving its lifecycle and extension points. */
    public function render(): string
    {
        return ($this->render)($this->config());
    }

    /** Rendering through echo has the same behavior as explicit render(). */
    public function __toString(): string
    {
        return $this->render();
    }
}
