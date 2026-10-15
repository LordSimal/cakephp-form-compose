<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\RecordingRenderer;
use FormCompose\Test\FormTestCase;
use stdClass;

/**
 * Verifies template metadata through real elements and the custom renderer contract.
 */
class TemplateDataTest extends FormTestCase
{
    public function testExplicitAndParentThemeTemplatesReceiveDataAndEscapeAtOutput(): void
    {
        $form = $this->helper()->bind(theme: new Theme(['themes/data']));
        $form->open();

        $html = $form->control('email', [
            'template' => 'themes/data/control',
            'templateData' => ['marker' => 'contact', 'content' => '<script>alert(1)</script>'],
        ]);
        $trusted = $form->control('name', ['templateData' => ['content' => new Html('<strong>Trusted</strong>')]]);
        $form->close();

        static::assertSame(2, substr_count($html, 'data-template-marker="contact"'));
        static::assertSame(2, substr_count($html, '&lt;script&gt;alert(1)&lt;/script&gt;'));
        static::assertStringNotContainsString('<script>', $html);
        static::assertStringContainsString('name="email"', $html);
        static::assertStringContainsString('<strong>Trusted</strong>', $trusted);
    }

    public function testPartsNativeChoicesAndCustomRenderersReceiveUnmodifiedValues(): void
    {
        $helper = $this->helper();
        $renderer = new RecordingRenderer(new PhpRenderer($helper->getView()));
        $form = $helper->bind(new ArrayContext([
            'errors' => ['status' => ['invalid' => 'Invalid']],
        ]), renderer: $renderer);
        $account = new stdClass();
        $data = ['marker' => 'shared', 'account' => $account, 'off' => false, 'zero' => 0, 'nil' => null];
        $parts = [
            'label',
            'help',
            'errors',
            'input',
            'native',
            'fallback',
            'choice',
            'choice-group',
            'option',
            'option-group',
        ];
        $templates = array_fill_keys($parts, 'themes/data/part');
        $form->open();
        $html = $form->control('status', [
            'type' => 'radio',
            'options' => ['Group' => ['a' => 'A']],
            'help' => 'Help',
            'templates' => $templates,
            'templateData' => $data,
        ]);
        $html .= $form->control('category', [
            'options' => ['Group' => ['a' => 'A']],
            'multiple' => true,
            'templates' => $templates,
            'templateData' => $data,
        ]);
        $form->close();

        $rendered = [];
        foreach ($renderer->calls as $call) {
            if ($call['node']->scope !== 'field') {
                continue;
            }

            static::assertSame($data, $call['node']->variables['templateData']);
            $rendered[] = $call['part'];
        }

        foreach (['control', ...$parts] as $part) {
            static::assertContains($part, $rendered);
        }

        static::assertStringContainsString('template-data: shared', $html);
        static::assertStringNotContainsString('templateData=', $html);
        static::assertStringNotContainsString('account=', $html);
    }

    public function testDefinitionsAndInlineOverridesReplaceTheBagWithoutMutatingSharedData(): void
    {
        $contact = FormDefinition::make()->add(FieldDefinition::make('email')->with([
            'template' => 'themes/data/control',
            'templateData' => ['marker' => 'original', 'content' => 'Original'],
        ]));
        $customized = $contact->with('email', ['templateData' => ['marker' => 'customized']]);
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->definition($customized);
        $form->close();
        static::assertStringContainsString('data-template-marker="customized"', $html);
        static::assertStringNotContainsString('Original', $html);
        static::assertSame(
            ['marker' => 'original', 'content' => 'Original'],
            $contact->field('email')->options['templateData'],
        );
        $inline = $form->field($contact->field('email'), ['templateData' => []]);
        static::assertSame([], $inline->state->options['templateData']);
    }

    public function testFormDataIsLocalToFormPartsAndButtonDataIsIndependent(): void
    {
        $helper = $this->helper(attributes: ['csrfToken' => bin2hex(random_bytes(16)), 'formTokenData' => []]);
        $renderer = new RecordingRenderer(new PhpRenderer($helper->getView()));
        $form = $helper->bind(
            null,
            [
                'method' => 'patch',
                'templateData' => ['marker' => 'binding', 'extra' => 'replaced'],
                'templates' => array_fill_keys(['open', 'close', 'hidden'], 'themes/data/part'),
            ],
            renderer: $renderer,
        );
        $html = $form->open(['templateData' => ['marker' => 'form']]);
        $html .= $form->control('email');
        $html .= $form->submit('Save', [
            'templates' => ['control' => 'themes/data/part'],
            'templateData' => ['marker' => 'button'],
        ]);
        $html .= $form->submit('Cancel');
        $html .= $form->close();
        $buttonData = [];
        $formParts = [];
        foreach ($renderer->calls as $call) {
            /** @var array<string, mixed> $data */
            $data = $call['node']->variables['templateData'];
            if ($call['node']->scope === 'form') {
                static::assertSame(['marker' => 'form'], $data);
                $formParts[] = $call['part'];
            } elseif ($call['node']->scope === 'button') {
                $buttonData[] = $data;
            } else {
                static::assertSame([], $data);
            }
        }

        static::assertContains('open', $formParts);
        static::assertContains('close', $formParts);
        static::assertContains('hidden', $formParts);
        static::assertSame([['marker' => 'button'], []], $buttonData);
        static::assertStringContainsString('template-data: form', $html);
        static::assertStringContainsString('template-data: button', $html);
        static::assertStringNotContainsString('templateData=', $html);
        static::assertStringNotContainsString('marker=', $html);
        static::assertStringNotContainsString('replaced', $html);
    }

    public function testBindingDataAndEmptyOpeningOverridesFollowNormalPrecedence(): void
    {
        $helper = $this->helper();
        $first = $helper->bind(null, [
            'templateData' => ['marker' => 'bound'],
            'templates' => ['open' => 'themes/data/part'],
        ]);
        static::assertStringContainsString('template-data: bound', $first->open());
        $first->close();
        $second = $helper->bind(null, [
            'templateData' => ['marker' => 'bound'],
            'templates' => ['open' => 'themes/data/part'],
        ]);
        static::assertStringContainsString('template-data: empty', $second->open(['templateData' => []]));
        $second->close();
    }
}
