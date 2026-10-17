<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Form\FormSession;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;

/**
 * Attempts one late input to verify the form cannot change after tokens are prepared.
 */
final class AppendInputRenderer implements RendererInterface
{
    private bool $attempted = false;

    /**
     * Delegate ordinary rendering to the native PHP renderer.
     */
    public function __construct(private readonly RendererInterface $renderer)
    {
    }

    /**
     * Try adding a successful control from inside a closing template once.
     */
    public function render(string $part, RenderNode $node): string
    {
        if (!$this->attempted && $node->scope === 'form' && $part === 'close') {
            $this->attempted = true;
            /** @var FormSession $form */
            $form = $node->variables['form'];
            $form->control('late');
        }

        return $this->renderer->render($part, $node);
    }
}
