<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Form\PostAction;
use FormCompose\FormSession;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\PostActionRenderer;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use RuntimeException;

/**
 * Verifies action failure isolation and normal helper/renderer extension boundaries.
 */
class PostActionRendererTest extends FormTestCase
{
    public function testFailedTriggersDoNotCommitBlocksOrSuppressARetry(): void
    {
        $helper = $this->helper();
        $renderer = new class(new PhpRenderer($helper->getView())) implements RendererInterface {
            protected bool $fail = true;

            public function __construct(protected readonly RendererInterface $delegate)
            {
            }

            public function render(string $part, RenderNode $node): string
            {
                if ($part === 'control' && $node->scope === 'action' && $this->fail) {
                    $this->fail = false;
                    throw new RuntimeException('Trigger failed.');
                }

                return $this->delegate->render($part, $node);
            }
        };
        try {
            $helper->postLink('Delete', '/delete', ['block' => 'actions'], renderer: $renderer);
            static::fail('Expected trigger rendering failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Trigger failed.', $runtimeException->getMessage());
            static::assertSame('', $helper->getView()->fetch('actions'));
        }

        $html = $helper->postLink('Retry', '/retry', ['block' => 'actions'], renderer: $renderer);
        static::assertStringContainsString('Retry', $html);
        static::assertStringContainsString('action="/retry"', $helper->getView()->fetch('actions'));
        static::assertStringNotContainsString('action="/delete"', $helper->getView()->fetch('actions'));
    }

    public function testCustomActionRenderingAndSessionOverridesAreUsedByPublicShortcuts(): void
    {
        $helper = new class($this->helper()->getView()) extends FormHelper {
            protected function createPostActionRenderer(): PostActionRenderer
            {
                return new class(
                    $this->getView(),
                    fn(array $options, RenderContext $rendering): FormSession => $this->bind(
                        null,
                        $options,
                        $rendering->theme,
                        $rendering->renderer,
                    ),
                ) extends PostActionRenderer {
                    protected function trigger(
                        string|Html $label,
                        PostAction $action,
                        FormSession $form,
                        RenderContext $rendering,
                    ): string {
                        return (
                            '<span data-custom-action>'
                                . parent::trigger($label, $action, $form, $rendering)
                                . '</span>'
                        );
                    }
                };
            }

            protected function createSession(
                FormBinding $binding,
                RenderContext $rendering,
                BlueprintFactory $blueprints,
            ): FormSession {
                return new class($binding, $rendering, $blueprints) extends FormSession {
                    /** @param array<string, mixed> $attributes Form opening options. */
                    public function open(array $attributes = []): string
                    {
                        return parent::open($attributes + ['data-custom-session' => true]);
                    }
                };
            }
        };
        $html = $helper->postButton('Delete', '/delete');
        static::assertStringContainsString('data-custom-session', $html);
        static::assertStringContainsString('data-custom-action', $html);
    }
}
