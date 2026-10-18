<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use RuntimeException;

class ThemeTest extends FormTestCase
{
    public function testLayeredTemplatesAndParentRendering(): void
    {
        $helper = $this->helper();
        $theme = new Theme(['themes/application', 'themes/library']);
        $form = $helper->form()->theme($theme)->url('/articles/save')->bind();
        $form->open();

        $html = $form->control('email')->type('email')->render();
        static::assertStringContainsString('<section data-layout="application">', $html);
        static::assertStringContainsString('<article data-layout="library">', $html);
        static::assertStringContainsString('type="email"', $html);
        $form->close();
    }

    public function testVariantAndExplicitFieldTemplates(): void
    {
        $form = $this->helper()
            ->form()
            ->theme(new Theme(['themes/application']))
            ->url('/articles/save')
            ->bind();
        $form->open();
        static::assertStringContainsString(
            'data-variant="compact"',
            $form->control('name')->variant('compact')->render(),
        );
        static::assertStringContainsString(
            'data-field="custom"',
            $form->control('email')->partTemplate('input', 'themes/custom/input')->render(),
        );
        $form->close();
    }

    public function testFluentThemesKeepEarlierLayersAndDefaultsIndependent(): void
    {
        $original = Theme::builder()->layer('themes/library')->defaults('input', ['class' => 'original']);
        $derived = $original->layer('themes/application')->defaults('input', ['class' => 'derived']);
        $original->defaults('input', ['class' => 'ignored']);
        static::assertSame(['themes/library'], $original->layers);
        static::assertSame(['themes/library', 'themes/application'], $derived->layers);
        $form = $this->helper()->form()->theme($original)->bind();
        $html = $form->open() . $form->control('title')->render() . $form->close();
        static::assertSame('original', $this->xpath($html)->evaluate('string(//input/@class)'));
    }

    public function testFluentThemeLayersValidatePathsImmediately(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Theme::builder()->layer('../outside');
    }

    public function testInvalidThemePathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Theme(['../outside']);
    }

    public function testInvalidVariantIsRejected(): void
    {
        $form = $this->helper()->form()->url('/articles/save')->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->control('name')->variant('../../outside')->render();
    }

    public function testMissingPartHasClearFailure(): void
    {
        $helper = $this->helper();
        $field = $helper->form()->bind()->field('name')->resolve();
        $this->expectException(RuntimeException::class);
        new PhpRenderer($helper->getView())->render('missing', RenderNode::field($field, new Attributes()));
    }

    public function testInvalidPartNameIsRejected(): void
    {
        $helper = $this->helper();
        $this->expectException(InvalidArgumentException::class);
        new PhpRenderer($helper->getView())->render('../invalid', RenderNode::field(
            $helper->form()->bind()->field('name')->resolve(),
            new Attributes(),
        ));
    }
}
