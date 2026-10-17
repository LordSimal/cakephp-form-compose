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
        $form = $helper->bind(theme: $theme);
        $form->open(['url' => '/articles/save']);

        $html = $form->control('email', ['type' => 'email']);
        static::assertStringContainsString('<section data-layout="application">', $html);
        static::assertStringContainsString('<article data-layout="library">', $html);
        static::assertStringContainsString('type="email"', $html);
        $form->close();
    }

    public function testVariantAndExplicitFieldTemplates(): void
    {
        $form = $this->helper()->bind(theme: new Theme(['themes/application']));
        $form->open(['url' => '/articles/save']);
        static::assertStringContainsString('data-variant="compact"', $form->control('name', ['variant' => 'compact']));
        static::assertStringContainsString('data-field="custom"', $form->control('email', ['templates' => [
            'input' => 'themes/custom/input',
        ]]));
        $form->close();
    }

    public function testInvalidThemePathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Theme(['../outside']);
    }

    public function testInvalidVariantIsRejected(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);
        $this->expectException(InvalidArgumentException::class);
        $form->control('name', ['variant' => '../../outside']);
    }

    public function testMissingPartHasClearFailure(): void
    {
        $helper = $this->helper();
        $field = $helper->bind()->field('name');
        $this->expectException(RuntimeException::class);
        new PhpRenderer($helper->getView())->render('missing', RenderNode::field($field, new Attributes()));
    }

    public function testInvalidPartNameIsRejected(): void
    {
        $helper = $this->helper();
        $this->expectException(InvalidArgumentException::class);
        new PhpRenderer($helper->getView())->render('../invalid', RenderNode::field(
            $helper->bind()->field('name'),
            new Attributes(),
        ));
    }
}
