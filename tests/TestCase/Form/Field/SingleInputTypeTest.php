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
        $form = $this->helper(['article' => ['body' => '</textarea><script>bad()</script>']])->bind(null, [
            'types' => ['markdown' => new MarkdownType()],
        ]);
        $field = $form->field('article.body', ['type' => 'markdown', 'label' => 'Body', 'required' => true]);
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
        $form = $this->helper()->bind(null, ['types' => ['scalar' => new SingleInputType()]]);
        $html =
            $form->open()
            . $form->control('amount', [
                'type' => 'scalar',
                'value' => 0,
                'templates' => ['widget' => 'widgets/parent'],
            ])
            . $form->close();
        static::assertStringContainsString('data-parent-layout', $html);
        static::assertStringContainsString('type="text" value="0"', $html);
    }

    public function testScalarSubmissionsExcludeDisabledControlsAndSupportExplicitUnlocking(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->bind(null, ['types' => ['markdown' => new MarkdownType()]]);
        $html =
            $form->open()
            . $form->control('body', ['type' => 'markdown'])
            . $form->control('disabled', ['type' => 'markdown', 'disabled' => true])
            . $form->control('optional', ['type' => 'markdown', 'secure' => false])
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
