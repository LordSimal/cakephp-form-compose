<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Test\CompositionTestCase;

/**
 * Verifies public blueprint names and aliases through real discovery and rendering.
 */
class BlueprintApiTest extends CompositionTestCase
{
    public function testHelperRenderAcceptsANamedBlueprintAliasAndBoundData(): void
    {
        $html = $this->helper()
            ->form(new ArrayContext(['defaults' => ['email' => 'reader@example.test']]))
            ->blueprint('profile')
            ->submit(false)
            ->render();
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
        $complete = $helper->form()->blueprint('profile')->submit(false)->bind()->render();
        static::assertSame(
            'reader@example.test',
            $this->xpath($complete)->evaluate('string(//input[@name="email"]/@value)'),
        );
        $form = $helper->form()->bind();
        $html = $form->open() . $form->blueprint(blueprint: 'composed-author', at: 'author')->render() . $form->close();
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
        ])
            ->form()
            ->blueprint('named-parent')
            ->submit(false)
            ->bind();
        $definition = $form->binding->definition;
        static::assertInstanceOf(FormDefinition::class, $definition);
        static::assertSame('profile', $definition->index->repeats['reviewers']->blueprint);
        static::assertSame('profile', $definition->index->repeats['contributors']->blueprint);
        static::assertInstanceOf(FormDefinition::class, $definition->index->repeats['reviewers']->rows);
        $html = $form->render();
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
        static::assertSame('email', $definition->field('email')->config->type);
    }

    public function testSessionRepeatsANamedBlueprintAliasAtItsAssociationPath(): void
    {
        $form = $this->helper([
            'reviewers' => [2 => ['email' => 'reviewer@example.test']],
        ])
            ->form()
            ->bind();
        $html =
            $form->open()
            . $form->embedMany(path: 'reviewers', blueprint: 'profile')->keys([2])->render()
            . $form->close();
        static::assertSame(
            'reviewer@example.test',
            $this->xpath($html)->evaluate('string(//input[@name="reviewers[2][email]"]/@value)'),
        );
    }
}
