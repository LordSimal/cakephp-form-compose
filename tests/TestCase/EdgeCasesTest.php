<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use Cake\View\Form\ContextFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\RenderNode;
use FormCompose\Test\Fixture\Status;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use RuntimeException;

class EdgeCasesTest extends FormTestCase
{
    public function testPluginIdentityAndHelperAliasUseCakeConventions(): void
    {
        $plugin = new FormComposePlugin();
        static::assertSame('FormCompose', $plugin->getName());
        $helper = $this->helper()->getView()->loadHelper('Compose', ['className' => 'FormCompose.Form']);
        static::assertInstanceOf(FormHelper::class, $helper);
    }

    public function testCustomNamesAndBackedEnums(): void
    {
        $form = $this->helper()->bind();
        $field = $form->field('status', [
            'name' => 'custom[status]',
            'id' => 'custom-status',
            'value' => Status::Published,
        ]);
        static::assertSame('published', $field->state->value);
        static::assertSame('custom[status]', $field->state->name);
        static::assertSame('custom-status', $field->state->id);
    }

    public function testCustomNameCannotInjectMarkup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind()->field('name', ['name' => 'name" autofocus="autofocus']);
    }

    public function testInvalidControlTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind()->field('name', ['type' => '../invalid']);
    }

    public function testNativeChoiceContentAndClassesAreEscaped(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save']);

        $checkbox = $form->control('flag', [
            'type' => 'checkbox',
            'value' => '"><script>',
            'checkedValue' => '"><script>',
        ]);
        static::assertStringNotContainsString('<script>', $checkbox);
        static::assertStringContainsString('checked="checked"', $checkbox);
        $group = $form->control('tags', ['type' => 'multicheckbox', 'options' => ['<script>' => ['a' => '<b>A</b>']]]);
        static::assertStringNotContainsString('<script>', $group);
        static::assertStringContainsString('&lt;script&gt;', $group);
        $html = $form->control('plain', [
            'attributes' => ['class' => ['wide' => true, 'hidden' => false]],
            'hiddenField' => false,
        ]);
        static::assertStringContainsString('class="wide"', $html);
        $form->close();
    }

    public function testFailedCustomRenderingDoesNotRegisterAnInput(): void
    {
        $renderer = new class implements RendererInterface {
            public function render(string $part, RenderNode $node): string
            {
                if ($node->scope === 'field') {
                    throw new RuntimeException('Rendering failed');
                }

                return '';
            }
        };
        $form = $this->helper()->bind(renderer: $renderer);
        $form->open(['url' => '/articles/save']);

        $field = $form->field('name');
        try {
            $field->input();
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Rendering failed', $runtimeException->getMessage());
        }

        $field->register();
        static::assertSame('', $form->close());
    }

    public function testCustomContextFactoryAndDateInference(): void
    {
        $factory = new ContextFactory();
        $factory->addProvider(
            'custom',
            static fn(): ArrayContext => new ArrayContext([
                'schema' => ['when' => ['type' => 'date']],
                'data' => ['when' => '2026-10-15'],
            ]),
        );
        $helper = $this->helper();
        $helper->setConfig('contextFactory', $factory);

        $form = $helper->bind('custom source');
        static::assertSame('date', $form->field('when')->state->type);
        $form->open(['url' => '/articles/save']);
        static::assertStringContainsString('value="2026-10-15"', $form->control('when'));
        $form->close();
    }

    public function testCheckboxCanOmitItsFallbackAndOpenCanEnableMultipart(): void
    {
        $form = $this->helper()->bind();
        $form->open(['url' => '/articles/save', 'enctype' => 'multipart/form-data']);
        static::assertStringNotContainsString('hidden', $form->control('flag', [
            'type' => 'checkbox',
            'hiddenField' => false,
        ]));
        static::assertStringContainsString('type="file"', $form->control('upload', ['type' => 'file']));
        $form->close();
    }
}
