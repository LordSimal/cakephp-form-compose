<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Closure;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use RuntimeException;

/**
 * Exercises actual template resolution and safe, nested diagnostic comments.
 */
class ThemeDebugTest extends FormTestCase
{
    public function testLookupShowsActualAttemptsInOrderAndStopsAtTheWinner(): void
    {
        $helper = $this->helper();
        $renderer = new PhpRenderer($helper->getView(), new Theme(['themes/application']), themeDebug: true);
        $form = $helper->bind(renderer: $renderer);
        $form->open();

        $html = $form->field('author.email', ['type' => 'email', 'variant' => 'compact'])->label();
        static::assertStringContainsString(
            "scope: field\npart: label\ntype: email\nfield: author.email\nvariant: compact",
            $html,
        );
        static::assertStringContainsString(
            "lookup:\n  MISS themes/application/variants/compact/label\n  MISS themes/application/email/label\n"
                . "  MISS themes/application/label\n  MISS FormCompose.form/base/variants/compact/label\n"
                . "  MISS FormCompose.form/base/email/label\n  MATCH FormCompose.form/base/label",
            $html,
        );
        static::assertStringContainsString('element: FormCompose.form/base/label', $html);
        static::assertStringContainsString('<!-- FormCompose END part=label -->', $html);
        $form->close();
    }

    public function testVariantsAndParentDelegationRetainTheirNestedLayerBoundaries(): void
    {
        $helper = $this->helper();
        $theme = new Theme(['themes/application', 'themes/library']);
        $form = $helper->bind(renderer: new PhpRenderer($helper->getView(), $theme, themeDebug: true));
        $form->open();

        $html = $form->control('email', ['type' => 'email', 'variant' => 'compact']);
        static::assertStringContainsString('MATCH themes/application/variants/compact/control', $html);
        static::assertStringNotContainsString('MISS themes/application/email/control', $html);
        static::assertStringContainsString(
            '<aside data-variant="compact"><!-- FormCompose PARENT --><!-- FormCompose BEGIN',
            $html,
        );
        static::assertStringContainsString('source: layer themes/library', $html);
        static::assertStringContainsString('MATCH themes/library/control', $html);
        static::assertStringContainsString('element: FormCompose.form/base/control', $html);
        static::assertSame(2, substr_count($html, '<!-- FormCompose PARENT -->'));
        $form->close();
    }

    public function testExplicitOverrideParentResumesAtTheFirstThemeLayer(): void
    {
        $helper = $this->helper();
        $renderer = new PhpRenderer($helper->getView(), new Theme(['themes/application']), themeDebug: true);
        $form = $helper->bind(renderer: $renderer);
        $form->open();

        $html = $form->control('title', ['template' => 'widgets/parent']);
        static::assertStringStartsWith(
            "<!-- FormCompose BEGIN\nscope: field\npart: control\ntype: text\nfield: title\n"
                . "source: explicit override\nelement: widgets/parent -->",
            $html,
        );
        static::assertStringContainsString('MATCH themes/application/control', $html);
        static::assertSame(2, substr_count($html, '<!-- FormCompose PARENT -->'));
        $form->close();
    }

    public function testEmptyPartsAndEmptyParentResultsStayEmpty(): void
    {
        $helper = $this->helper();
        $renderer = new PhpRenderer($helper->getView(), new Theme(['themes/debug']), themeDebug: true);
        static::assertSame('', $renderer->render('label', new RenderNode('field', 'text', [])));
        static::assertSame('', $renderer->render(
            'label',
            new RenderNode(
                'field',
                'text',
                [],
                [
                    'templates' => ['label' => 'themes/debug/label'],
                ],
            ),
        ));
        $html = $renderer->render(
            'label',
            new RenderNode(
                'field',
                'text',
                [],
                [
                    'templates' => ['label' => 'widgets/parent'],
                ],
            ),
        );
        static::assertStringNotContainsString('FormCompose PARENT', $html);
        static::assertStringContainsString('<article data-parent-layout></article>', $html);
    }

    public function testHostileMetadataCannotEscapeCommentsOrSpoofDiagnosticLines(): void
    {
        $helper = $this->helper();
        $field = $helper->bind()->field('name--');
        $renderer = new PhpRenderer($helper->getView(), themeDebug: true);
        $html = $renderer->render(
            'label',
            new RenderNode(
                'field',
                'text',
                ['field' => $field],
                [
                    'variant' => "--><img src=x onerror=bad>\r\nspoof",
                    'templates' => ['label' => 'themes/debug/constant'],
                    'templateData' => ['message' => 'hidden metadata'],
                ],
            ),
        );
        static::assertStringContainsString('field: name&#45;&#45;', $html);
        static::assertStringContainsString('variant: &#45;&#45;&gt;&lt;img src=x onerror=bad&gt;\\r\\nspoof', $html);
        static::assertStringNotContainsString('hidden metadata', $html);
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//script | //img)'));
        static::assertSame('Constant', $this->xpath($html)->evaluate('string(//span)'));
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//comment())'));
    }

    public function testMissingTemplatesStillThrowWithoutLeakingTraceOutputIntoLaterRenders(): void
    {
        $helper = $this->helper();
        $renderer = new PhpRenderer($helper->getView(), themeDebug: true);
        $field = $helper->bind()->field('title');
        $node = RenderNode::field($field, new Attributes());
        $this->expectOutputString('');
        try {
            $renderer->render('missing', $node);
            static::fail('Expected missing template lookup to throw.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('No template for field part: missing', $runtimeException->getMessage());
        }

        $html = $renderer->render('label', $node);
        static::assertStringNotContainsString('missing', $html);
        static::assertStringContainsString('MATCH FormCompose.form/base/label', $html);
    }

    public function testRendererSubclassElementOverridesStillRunWithDiagnosticsEnabled(): void
    {
        $helper = $this->helper();
        $renderer = new class($helper->getView(), themeDebug: true) extends PhpRenderer {
            /**
             * Supply application markup through the ordinary execution extension point.
             */
            protected function element(string $element, RenderNode $node, Closure $parent): string
            {
                return '<b>Application override</b>' . parent::element($element, $node, $parent);
            }
        };
        $html = $renderer->render('label', RenderNode::field($helper->bind()->field('title'), new Attributes()));
        static::assertStringContainsString('element: FormCompose.form/base/label', $html);
        static::assertStringContainsString('<b>Application override</b>', $html);
    }
}
