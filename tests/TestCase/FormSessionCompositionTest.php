<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\FieldDefinition;
use FormCompose\FormDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\DynamicPostForm;
use FormCompose\Test\Fixture\Composition\EnvelopeForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use FormCompose\Test\Fixture\ContactFormHelper;

/**
 * Exercises layouts and relative embeds through public form-building entry points.
 */
class FormSessionCompositionTest extends CompositionTestCase
{
    public function testCustomLayoutsUseCompiledChildrenAndParentOverrides(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'data' => [
                'title' => 'Post <title>',
                'author' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'label' => 'Family name'],
            ],
        ]));
        $html = $form->open() . $form->definition('composed-post') . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//article[@data-post]/section[@data-author])'));
        static::assertSame(2.0, $xpath->evaluate('count(//div[@class="grid grid-cols-2 gap-4"]//input)'));
        static::assertSame('Ada', $xpath->evaluate('string(//input[@name="author[first_name]"]/@value)'));
        static::assertStringContainsString('Post &lt;title&gt;', $html);
        static::assertStringContainsString('&lt;Author&gt;', $html);
        static::assertStringContainsString('Family name', $html);
        static::assertStringContainsString('Author name from parent', $html);
        static::assertStringNotContainsString('name="author[internal]"', $html);
        static::assertSame(['author', 'post'], array_column($this->probe->calls, 'name'));
        static::assertNull($form->binding->definition);
        static::assertSame([], $form->field('author.first_name')->state->options);
    }

    public function testDefaultLayoutPreservesOrderingAndHonorsChildLayouts(): void
    {
        $definition = FormDefinition::make()
            ->add(FieldDefinition::make('before'))
            ->embed('author', $this->factory->definition(AuthorForm::class, $this->context()))
            ->add(FieldDefinition::make('after'));
        $form = $this->helper()->bind(definition: $definition);
        $html = $form->open() . $form->definition() . $form->close();
        static::assertLessThan(strpos($html, 'data-author'), strpos($html, 'name="before"'));
        static::assertLessThan(strpos($html, 'name="after"'), strpos($html, 'name="author[id]"'));
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
        static::assertSame('hidden', $form->field('author.id')->state->type);
    }

    public function testDirectMountScopesHooksAndKeepsRenderOptionsLocal(): void
    {
        $form = $this->helper(['billing' => ['first_name' => 'Submitted']])->bind(new ArrayContext([
            'data' => [
                'billing' => ['label' => 'Billing surname'],
                'shipping' => ['label' => 'Shipping surname'],
            ],
        ]));
        $html = $form->open();
        $html .= $form->definition(AuthorForm::class, at: 'billing', options: [
            'attributes' => ['data-scope' => '<billing>'],
            'templateData' => ['heading' => '<Billing>'],
        ]);
        $html .= $form->definition('composed-author', at: 'shipping');
        $html .= $form->close();
        static::assertStringContainsString('name="billing[first_name]"', $html);
        static::assertStringContainsString('value="Submitted"', $html);
        static::assertStringContainsString('Billing surname', $html);
        static::assertStringContainsString('Shipping surname', $html);
        static::assertStringContainsString('data-scope="&lt;billing&gt;"', $html);
        static::assertStringContainsString('&lt;Billing&gt;', $html);
        static::assertStringContainsString('&lt;Author&gt;', $html);
        static::assertNull($form->binding->definition);
        static::assertCount(2, $this->probe->calls);
    }

    public function testBuilderEmbedsSupportIndependentIndexedOccurrences(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'data' => [
                'author' => ['first_name' => 'Root', 'label' => 'Root surname'],
                'comments' => [['author' => ['first_name' => 'Nested', 'label' => 'Nested surname']]],
                'details' => ['summary' => 'Summary'],
            ],
        ]), definition: DynamicPostForm::class);
        $html = $form->open() . $form->definition() . $form->close();
        static::assertStringContainsString('name="comments[0][author][first_name]"', $html);
        static::assertStringContainsString('value="Nested"', $html);
        static::assertStringContainsString('value="Root"', $html);
        static::assertStringContainsString('value="Summary"', $html);
        static::assertSame('Root surname', $form->field('author.last_name')->state->options['label']);
        static::assertSame('Nested surname', $form->field('comments.0.author.last_name')->state->options['label']);
        static::assertSame(['calls' => 1], $form->field('author.first_name')->state->options['templateData']);
        static::assertSame(
            ['calls' => 1],
            $form->field('comments.0.author.first_name')->state->options['templateData'],
        );
        static::assertCount(2, $this->probe->calls);
    }

    public function testDeepEmbedsAccumulateTheirScopeWithoutAddingForms(): void
    {
        $helper = $this->helper();
        $form = $helper->bind(new ArrayContext(['data' => ['post' => ['author' => ['first_name' => 'Deep']]]]));
        $html = $form->open() . $form->definition(EnvelopeForm::class) . $form->close();
        static::assertStringContainsString('name="post[author][first_name]"', $html);
        static::assertStringContainsString('id="form-compose-1-post-author-first_name"', $html);
        static::assertStringContainsString('value="Deep"', $html);
        static::assertSame('Deep', $this->probe->calls[0]['context']->context->val('first_name'));
        static::assertSame($helper->getView()->getRequest(), $this->probe->calls[0]['context']->request);
        static::assertSame(1, substr_count($html, '<form '));
    }

    public function testRemovedFieldsDoNotReappearAndConditionalWrappersDisappear(): void
    {
        $form = $this->helper(attributes: ['stripParentEmail' => true])->bind(definition: PostForm::class);
        $html = $form->open() . $form->definition() . $form->close();
        static::assertStringNotContainsString('data-email', $html);
        static::assertStringNotContainsString('name="author[email]"', $html);
        static::assertStringNotContainsString('name="author[internal]"', $html);
        static::assertStringContainsString('name="author[id]"', $html);
        static::assertNotNull($form->binding->definition);
        static::assertFalse($form->binding->definition->has('author.email'));
        static::assertSame('text', $form->field('author.email')->state->type);
    }

    public function testDefinitionThemeFallbackAndPerCallOverrides(): void
    {
        $definition = FormDefinition::make()->add(FieldDefinition::make('title'));
        $form = $this->helper()->bind(theme: new Theme(['themes/definition']));
        $html = $form->open() . $form->definition($definition) . $form->close();
        static::assertStringContainsString('data-default-definition', $html);
        static::assertStringContainsString('name="title"', $html);

        $form = $this->helper()->bind();
        $html =
            $form->open()
            . $form->definition(AuthorForm::class, options: [
                'template' => 'themes/definition/definition/control',
            ])
            . $form->close();
        static::assertStringContainsString('data-default-definition', $html);
        static::assertStringNotContainsString('data-author', $html);
        static::assertStringContainsString('name="first_name"', $html);
        static::assertStringContainsString('&lt;Work&gt; email', $html);
    }

    public function testScopedFieldsRetainApplicationResolverAndFieldSubclasses(): void
    {
        $helper = new ContactFormHelper($this->helper()->getView());
        $helper->setConfig('blueprintFactory', $this->factory);

        $form = $helper->bind();
        $definition = FormDefinition::make()->embed(
            'author',
            FormDefinition::make()->add(FieldDefinition::make('name')),
        );
        $html = $form->open() . $form->definition($definition) . $form->close();
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('name="author[name]"', $html);
    }
}
