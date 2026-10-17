<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\FormDefinition;
use FormCompose\Test\CompositionTestCase;

/**
 * Verifies public blueprint names and aliases through real discovery and rendering.
 */
class BlueprintApiTest extends CompositionTestCase
{
    public function testHelperRenderAcceptsANamedBlueprintAliasAndBoundData(): void
    {
        $html = $this->helper()->render(
            blueprint: 'profile',
            data: new ArrayContext(['defaults' => ['email' => 'reader@example.test']]),
            submit: false,
        );
        static::assertSame(1.0, $this->xpath($html)->evaluate('count(//form)'));
        static::assertSame(
            'reader@example.test',
            $this->xpath($html)->evaluate('string(//input[@name="email"]/@value)'),
        );
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//button)'));
    }

    public function testSessionRenderAndBodySelectionAcceptNamedBlueprintAliases(): void
    {
        $helper = $this->helper(['email' => 'reader@example.test']);
        $complete = $helper->bind()->render(blueprint: 'profile', submit: false);
        static::assertSame(
            'reader@example.test',
            $this->xpath($complete)->evaluate('string(//input[@name="email"]/@value)'),
        );
        $form = $helper->bind();
        $html = $form->open() . $form->blueprint(blueprint: 'composed-author', at: 'author') . $form->close();
        static::assertStringContainsString('name="author[email]"', $html);
        static::assertNull($form->binding->definition);
    }

    public function testNamedAliasEmbedsProduceCompiledDefinitionsAndScopedFields(): void
    {
        $form = $this->helper([
            'author' => ['email' => 'author@example.test'],
            'reviewers' => [0 => ['email' => 'reviewer@example.test']],
            'editor' => ['email' => 'editor@example.test'],
            'contributors' => [1 => ['email' => 'contributor@example.test']],
        ])->bind(blueprint: 'named-parent');
        $definition = $form->binding->definition;
        static::assertInstanceOf(FormDefinition::class, $definition);
        static::assertSame('profile', $definition->index->repeats['reviewers']->blueprint);
        static::assertSame('profile', $definition->index->repeats['contributors']->blueprint);
        static::assertInstanceOf(FormDefinition::class, $definition->index->repeats['reviewers']->rows);
        $html = $form->render(submit: false);
        $xpath = $this->xpath($html);
        foreach ([
            'author[email]' => 'author@example.test',
            'reviewers[0][email]' => 'reviewer@example.test',
            'editor[email]' => 'editor@example.test',
            'contributors[1][email]' => 'contributor@example.test',
        ] as $name => $value) {
            static::assertSame($value, $xpath->evaluate('string(//input[@name="' . $name . '"]/@value)'));
        }
    }

    public function testFactoryCompilesANamedAliasIntoADefinitionSnapshot(): void
    {
        $definition = $this->factory->compile(blueprint: 'profile', context: $this->context());
        static::assertInstanceOf(FormDefinition::class, $definition);
        static::assertSame('email', $definition->field('email')->options['type']);
    }

    public function testSessionRepeatsANamedBlueprintAliasAtItsAssociationPath(): void
    {
        $form = $this->helper([
            'reviewers' => [2 => ['email' => 'reviewer@example.test']],
        ])->bind();
        $html =
            $form->open()
            . $form->embedMany(path: 'reviewers', blueprint: 'profile', keys: [2])
            . $form->close();
        static::assertSame(
            'reviewer@example.test',
            $this->xpath($html)->evaluate('string(//input[@name="reviewers[2][email]"]/@value)'),
        );
    }
}
