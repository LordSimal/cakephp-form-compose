<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\InheritedPostForm;
use FormCompose\Test\Fixture\Composition\Invalid\ChangedEntryKind;
use FormCompose\Test\Fixture\Composition\Invalid\CycleA;
use FormCompose\Test\Fixture\Composition\Invalid\DuplicateEmbed;
use FormCompose\Test\Fixture\Composition\Invalid\DynamicCycle;
use FormCompose\Test\Fixture\Composition\Invalid\OverlappingFields;
use FormCompose\Test\Fixture\Composition\PostForm;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Covers static metadata inheritance, scoped construction, and rejected composition.
 */
class BlueprintCompositionTest extends CompositionTestCase
{
    public function testInheritedEmbedsResolveOnlyTheSurvivingOccurrence(): void
    {
        $definition = $this->factory->compile(InheritedPostForm::class, $this->context());
        static::assertSame(
            ['title', 'author.first_name', 'author.last_name', 'author.email', 'author.id', 'body', 'extra'],
            array_column($definition->fields, 'path'),
        );
        static::assertSame('Inherited title', $definition->field('title')->config->label);
        static::assertSame('composition/post', $definition->presentation->templates['control']);
        static::assertSame(['inherited' => true], $definition->presentation->templateData);
        static::assertSame(['post', 'author'], array_column($this->probe->calls, 'name'));
    }

    /**
     * Supply invalid static and runtime trees through the same public factory.
     *
     * @return array<string, array{class-string, string}>
     */
    public static function invalidTrees(): array
    {
        return [
            'indirect cycle' => [CycleA::class, 'Circular blueprint embedding'],
            'runtime cycle' => [DynamicCycle::class, 'Circular blueprint embedding'],
            'duplicate mounts' => [DuplicateEmbed::class, 'duplicate field paths'],
            'qualified collision' => [OverlappingFields::class, 'duplicate field paths'],
            'incompatible inheritance' => [ChangedEntryKind::class, 'duplicate field paths'],
        ];
    }

    #[DataProvider('invalidTrees')]
    public function testInvalidTreesAreRejected(string $class, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->factory->compile($class, $this->context());
    }

    public function testCycleFailureDoesNotPoisonLaterResolutions(): void
    {
        try {
            $this->factory->compile(CycleA::class, $this->context());
            static::fail('Expected a cycle to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString(CycleA::class, $invalidArgumentException->getMessage());
        }

        static::assertTrue($this->factory->compile(PostForm::class, $this->context())->has('author.email'));
    }

    public function testSeparateResolutionsKeepRemovalHistoryAndHookStateIsolated(): void
    {
        $restricted = $this->helper(attributes: ['canEditEmail' => false])
            ->form()
            ->blueprint(AuthorForm::class)
            ->bind();
        $ordinary = $this->helper()->form()->blueprint(AuthorForm::class)->bind();
        static::assertNotNull($restricted->binding->definition);
        static::assertNotNull($ordinary->binding->definition);
        static::assertFalse($restricted->binding->definition->has('email'));
        static::assertTrue($ordinary->binding->definition->has('email'));
        static::assertSame(
            ['calls' => 1],
            $ordinary->binding->definition->field('first_name')->config->presentation->templateData,
        );
    }

    /**
     * Supply malformed embedding declarations that fail before discovery.
     *
     * @return array<string, array{string, string}>
     */
    public static function invalidEmbeds(): array
    {
        return [
            'empty path' => ['', 'profile'],
            'bad path' => ['author..name', 'profile'],
            'empty identifier' => ['author', ''],
            'leading whitespace' => ['author', ' profile'],
            'trailing whitespace' => ['author', 'profile '],
        ];
    }

    #[DataProvider('invalidEmbeds')]
    public function testInvalidEmbedsAreRejected(string $path, string $name): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $this->expectException(InvalidArgumentException::class);
        $form->blueprint($name, at: $path)->render();
    }

    public function testInvalidMountPathFailsBeforeRunningBlueprintHooks(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        try {
            $form->blueprint(blueprint: AuthorForm::class, at: 'author..name')->render();
            static::fail('Expected invalid mounting to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('dot-separated', $invalidArgumentException->getMessage());
        }

        static::assertSame([], $this->probe->calls);
        $form->close();
    }
}
