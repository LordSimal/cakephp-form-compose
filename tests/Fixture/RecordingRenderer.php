<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;

/**
 * Records the public renderer contract while delegating real template execution.
 */
class RecordingRenderer implements RendererInterface
{
    /** @var list<array{part: string, node: \FormCompose\Rendering\RenderNode}> */
    public array $calls = [];

    /**
     * Keep normal PHP rendering available for integration assertions.
     */
    public function __construct(protected readonly RendererInterface $renderer)
    {
    }

    /**
     * Capture each scoped part and its prepared template variables.
     */
    public function render(string $part, RenderNode $node): string
    {
        $this->calls[] = ['part' => $part, 'node' => $node];

        return $this->renderer->render($part, $node);
    }
}
