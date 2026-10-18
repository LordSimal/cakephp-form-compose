<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\Form\FormProtector;
use FormCompose\Form\Field\SingleInputType;
use FormCompose\Test\Fixture\Widget\MarkdownType;
use FormCompose\Test\FormTestCase;

/**
 * Verifies the simple scalar authoring path preserves escaping and submission contracts.
 */
class SingleInputTypeTest extends FormTestCase
{
    public function testEditorElementEscapesContentAndUsesTheSuppliedIdentity(): void
    {
        $form = $this->helper(['article' => ['body' => '</textarea><script>bad()</script>']])
            ->form()
            ->types(['markdown' => new MarkdownType()])
            ->bind();
        $field = $form->field('article.body')
            ->type('markdown')
            ->label('Body')
            ->required(true)
            ->resolve();
        $html = $form->open() . $field->control() . $form->close();
        static::assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;', $html);
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//script)'));
        static::assertSame('article[body]', $this->xpath($html)->evaluate('string(//textarea/@name)'));
        static::assertSame($field->state->id, $this->xpath($html)->evaluate('string(//label/@for)'));
        static::assertStringContainsString('data-editor="markdown"', $html);
        static::assertStringContainsString(' required', $html);
    }

    public function testLocalWidgetOverrideAndParentLookupRetainTheDefaultScalarValue(): void
    {
        $form = $this->helper()
            ->form()
            ->types(['scalar' => new SingleInputType()])
            ->bind();
        $html =
            $form->open()
            . $form->control('amount')
                ->type('scalar')
                ->value(0)
                ->partTemplate('widget', 'widgets/parent')
                ->render()
            . $form->close();
        static::assertStringContainsString('data-parent-layout', $html);
        static::assertStringContainsString('type="text" value="0"', $html);
    }

    public function testScalarSubmissionsExcludeDisabledControlsAndSupportExplicitUnlocking(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form()
            ->types(['markdown' => new MarkdownType()])
            ->bind();
        $html =
            $form->open()
            . $form->control('body')->type('markdown')->render()
            . $form->control('disabled')->type('markdown')->disabled(true)->render()
            . $form->control('optional')->type('markdown')->secure(false)->render()
            . $form->close();
        $tokens = [];
        foreach (['fields', 'unlocked', 'debug'] as $key) {
            $value = $this->xpath($html)->evaluate('string(//input[@name="_Token[' . $key . ']"]/@value)');
            static::assertIsString($value);
            $tokens[$key] = $value;
        }

        $payload = ['body' => 'Updated', '_Token' => $tokens];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        static::assertTrue(new FormProtector()->validate(
            $payload + ['optional' => 'Allowed'],
            '/articles/save',
            $session,
        ));
        static::assertFalse(new FormProtector()->validate(
            $payload + ['disabled' => 'Injected'],
            '/articles/save',
            $session,
        ));
    }
}
