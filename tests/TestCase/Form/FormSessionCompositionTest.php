<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Form\Field\FieldDefinition;
use FormCompose\Rendering\Theme;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\OrderedAuthorForm;
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
        $form = $this->helper()
            ->form(new ArrayContext([
                'data' => [
                    'title' => 'Post <title>',
                    'author' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'label' => 'Family name'],
                ],
            ]))
            ->bind();
        $html = $form->open() . $form->blueprint(blueprint: 'composed-post')->render() . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//article[@data-post]/section[@data-author])'));
        static::assertSame(2.0, $xpath->evaluate('count(//div[@class="grid grid-cols-2 gap-4"]//input)'));
        static::assertSame('Ada', $xpath->evaluate('string(//input[@name="author[first_name]"]/@value)'));
        static::assertStringContainsString('Post &lt;title&gt;', $html);
        static::assertStringContainsString('&lt;Author&gt;', $html);
        static::assertStringContainsString('Family name', $html);
        static::assertStringContainsString('Author name from parent', $html);
        static::assertStringNotContainsString('name="author[internal]"', $html);
        static::assertSame(['post', 'author'], array_column($this->probe->calls, 'name'));
        static::assertNull($form->binding->definition);
        static::assertNull($form->field('author.first_name')->resolve()->state->config->label);
    }

    public function testDefaultLayoutPreservesOrderingAndHonorsChildLayouts(): void
    {
        $form = $this->helper()->form()->blueprint(OrderedAuthorForm::class)->bind();
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        static::assertLessThan(strpos($html, 'data-author'), strpos($html, 'name="before"'));
        static::assertLessThan(strpos($html, 'name="after"'), strpos($html, 'name="author[id]"'));
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
        static::assertSame('hidden', $form->field('author.id')->resolve()->state->type);
    }

    public function testDirectMountScopesHooksAndKeepsRenderOptionsLocal(): void
    {
        $form = $this->helper(['billing' => ['first_name' => 'Submitted']])
            ->form(new ArrayContext([
                'data' => [
                    'billing' => ['label' => 'Billing surname'],
                    'shipping' => ['label' => 'Shipping surname'],
                ],
            ]))
            ->bind();
        $html = $form->open();
        $html .= $form->blueprint(blueprint: AuthorForm::class, at: 'billing')
            ->attributes(['data-scope' => '<billing>'])
            ->templateData(['heading' => '<Billing>'])
            ->render();
        $html .= $form->blueprint(blueprint: 'composed-author', at: 'shipping')->render();
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
        $form = $this->helper()
            ->form(new ArrayContext([
                'data' => [
                    'author' => ['first_name' => 'Root', 'label' => 'Root surname'],
                    'comments' => [['author' => ['first_name' => 'Nested', 'label' => 'Nested surname']]],
                    'details' => ['summary' => 'Summary'],
                ],
            ]))
            ->blueprint(DynamicPostForm::class)
            ->bind();
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        static::assertStringContainsString('name="comments[0][author][first_name]"', $html);
        static::assertStringContainsString('value="Nested"', $html);
        static::assertStringContainsString('value="Root"', $html);
        static::assertStringContainsString('value="Summary"', $html);
        static::assertSame('Root surname', $form->field('author.last_name')->resolve()->state->config->label);
        static::assertSame(
            'Nested surname',
            $form->field('comments.0.author.last_name')->resolve()->state->config->label,
        );
        static::assertSame(
            ['calls' => 1],
            $form->field('author.first_name')->resolve()->state->config->presentation->templateData,
        );
        static::assertSame(
            ['calls' => 1],
            $form->field('comments.0.author.first_name')->resolve()->state->config->presentation->templateData,
        );
        static::assertCount(2, $this->probe->calls);
    }

    public function testDeepEmbedsAccumulateTheirScopeWithoutAddingForms(): void
    {
        $helper = $this->helper();
        $form = $helper->form(new ArrayContext(['data' => ['post' => ['author' => ['first_name' => 'Deep']]]]))->bind();
        $html = $form->open() . $form->blueprint(blueprint: EnvelopeForm::class)->render() . $form->close();
        static::assertStringContainsString('name="post[author][first_name]"', $html);
        static::assertStringContainsString('id="form-compose-1-post.author.first_name"', $html);
        static::assertStringContainsString('value="Deep"', $html);
        static::assertSame('Deep', $this->probe->calls[1]['context']->context->val('first_name'));
        static::assertSame($helper->getView()->getRequest(), $this->probe->calls[0]['context']->request);
        static::assertSame(1, substr_count($html, '<form '));
    }

    public function testRemovedFieldsDoNotReappearAndConditionalWrappersDisappear(): void
    {
        $form = $this->helper(attributes: ['stripParentEmail' => true])->form()->blueprint(PostForm::class)->bind();
        $html = $form->open() . $form->blueprint()->render() . $form->close();
        static::assertStringNotContainsString('data-email', $html);
        static::assertStringNotContainsString('name="author[email]"', $html);
        static::assertStringNotContainsString('name="author[internal]"', $html);
        static::assertStringContainsString('name="author[id]"', $html);
        static::assertNotNull($form->binding->definition);
        static::assertFalse($form->binding->definition->has('author.email'));
        static::assertSame('email', $form->field('author.email')->resolve()->state->type);
    }

    public function testDefinitionThemeFallbackAndPerCallOverrides(): void
    {
        $definition = new FormDefinition([])->add(new FieldDefinition('title'));
        $form = $this->helper()
            ->form()
            ->theme(new Theme(['themes/blueprint']))
            ->bind();
        $html = $form->open() . $form->renderDefinition($definition) . $form->close();
        static::assertStringContainsString('data-default-definition', $html);
        static::assertStringContainsString('name="title"', $html);

        $form = $this->helper()->form()->bind();
        $html =
            $form->open()
            . $form->blueprint(blueprint: AuthorForm::class)
                ->partTemplate('control', 'themes/blueprint/blueprint/control')
                ->render()
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

        $form = $helper->form()->bind();
        $definition = new FormDefinition([])->embed(
            'author',
            new FormDefinition([])->add(new FieldDefinition('name')),
        );
        $html = $form->open() . $form->renderDefinition($definition) . $form->close();
        static::assertStringContainsString('data-contact-label', $html);
        static::assertStringContainsString('name="author[name]"', $html);
    }
}
