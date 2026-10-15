<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use RuntimeException;

/**
 * Simulates one template failure without changing normal rendering behavior.
 */
final class FailOnceRenderer implements RendererInterface
{
    private bool $failed = false;

    /**
     * Select a single scoped part to fail before delegating future renders.
     */
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly string $scope,
        private readonly string $part,
    ) {
    }

    /**
     * Fail once so lifecycle and security commit ordering can be verified.
     */
    public function render(string $part, RenderNode $node): string
    {
        if (!$this->failed && $node->scope === $this->scope && $part === $this->part) {
            $this->failed = true;
            throw new RuntimeException('Template failed');
        }

        return $this->renderer->render($part, $node);
    }
}
