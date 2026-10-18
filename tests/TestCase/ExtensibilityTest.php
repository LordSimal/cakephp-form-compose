<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Closure;
use FormCompose\Binding\FormBinding;
use FormCompose\Configuration\FormConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\Choice;
use FormCompose\Form\Field\NativeType;
use FormCompose\Form\FormConstruction;
use FormCompose\Form\FormLifecycle;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\PhpRenderer;
use FormCompose\Rendering\RenderNode;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\ContactFormHelper;
use FormCompose\Test\FormTestCase;

/**
 * Exercises subclass customization through ordinary form-building entry points.
 */
class ExtensibilityTest extends FormTestCase
{
    public function testHelperSubclassesCanCustomizeResolvedControls(): void
    {
        $helper = new ContactFormHelper($this->helper(attributes: ['formTokenData' => []])->getView());
        $form = $helper->form()->bind();
        $html = $form->open() . $form->control('website')->render() . $form->control('name')->render() . $form->close();
        static::assertStringContainsString('type="url"', $html);
        static::assertStringContainsString('value="https://example.test"', $html);
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('type="text"', $html);
        static::assertStringContainsString('_Token[fields]', $html);
    }

    public function testResolverSubclassesPreserveSubmittedAndExplicitValues(): void
    {
        $helper = new ContactFormHelper($this->helper(['website' => 'https://submitted.test'])->getView());
        $form = $helper->form()->bind();
        static::assertSame('https://submitted.test', $form->field('website')->resolve()->state->value);
        static::assertNull(
            $form->field('website')->value(null)->resolve()->state->value,
        );
        static::assertSame(
            'text',
            $form->field('website')->type('text')->resolve()->state->type,
        );
    }

    public function testNativeTypeSubclassesCanCustomizeIndividualChoices(): void
    {
        $helper = $this->helper();
        $theme = Theme::base();
        $type = new class(new PhpRenderer($helper->getView(), $theme), $theme) extends NativeType {
            /**
             * Add application metadata before the standard choice template renders.
             */
            protected function choice(BoundField $field, Choice $choice): string
            {
                return parent::choice($field, new Choice(
                    $choice->kind,
                    $choice->label,
                    $choice->attributes->merge(['data-enhanced-choice' => true]),
                    $choice->children,
                    $choice->presentation,
                ));
            }
        };
        $form = $helper->form()
            ->types(['radio' => $type])
            ->bind();
        $form->open();

        $html = $form->control('status')
            ->type('radio')
            ->choices(['a' => 'Active'])
            ->value('a')
            ->render();
        static::assertStringContainsString('data-enhanced-choice', $html);
        static::assertStringContainsString('checked="checked"', $html);
        static::assertStringContainsString('for="form-compose-1-status-a"', $html);
        $form->close();
    }

    public function testRendererSubclassesCanCustomizeVariablesAcrossThemeLayers(): void
    {
        $helper = $this->helper();
        $theme = new Theme(layers: ['themes/unified']);
        $renderer = new class($helper->getView(), $theme) extends PhpRenderer {
            /**
             * Add button metadata while retaining the normal element and parent lookup.
             */
            protected function element(string $element, RenderNode $node, Closure $parent): string
            {
                if ($node->scope === 'button') {
                    /** @var Attributes $attributes */
                    $attributes = $node->variables['attributes'];
                    $node = new RenderNode(
                        $node->scope,
                        $node->type,
                        array_replace($node->variables, [
                            'attributes' => $attributes->merge(['data-custom-renderer' => true]),
                        ]),
                        $node->presentation,
                    );
                }

                return parent::element($element, $node, $parent);
            }
        };
        $form = $helper->form()->theme($theme)->renderer($renderer)->bind();
        $form->open();

        $html = $form->submit('<Save>')->render();
        static::assertStringContainsString('data-button-shell', $html);
        static::assertStringContainsString('data-custom-renderer', $html);
        static::assertStringContainsString('&lt;Save&gt;', $html);
        $form->close();
    }

    public function testFormConstructionUsesOverriddenHiddenFieldPreparation(): void
    {
        $binding = $this->helper()->form()->bind()->binding;
        $construction = new class extends FormConstruction {
            /**
             * Annotate initial infrastructure while retaining standard method and CSRF inputs.
             *
             * @return list<\FormCompose\Rendering\Attributes>
             */
            protected function hidden(FormBinding $binding, string $method): array
            {
                return array_map(
                    static fn(Attributes $attributes): Attributes => $attributes->merge([
                        'data-infrastructure' => true,
                    ]),
                    parent::hidden($binding, $method),
                );
            }
        };
        $lifecycle = new FormLifecycle($binding, $construction);
        $state = $lifecycle->prepare(Theme::base(), FormConfig::builder()->method('patch')->config());
        static::assertSame('_method', $state->hidden[0]->values['name']);
        static::assertSame('PATCH', $state->hidden[0]->values['value']);
        static::assertTrue($state->hidden[0]->has('data-infrastructure'));
    }
}
