<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use FormCompose\Attribute\Embed;
use FormCompose\Blueprint\BlueprintCompiler;
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
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Covers static metadata inheritance, scoped construction, and rejected composition.
 */
class BlueprintCompositionTest extends CompositionTestCase
{
    public function testInheritedEmbedsResolveOnlyTheSurvivingOccurrence(): void
    {
        $definition = $this->factory->definition(InheritedPostForm::class, $this->context());
        static::assertSame(
            ['title', 'author.first_name', 'author.last_name', 'author.email', 'author.id', 'body', 'extra'],
            array_column($definition->fields, 'path'),
        );
        static::assertSame('Inherited title', $definition->field('title')->options['label']);
        static::assertSame('composition/post', $definition->options['template']);
        static::assertSame(['inherited' => true], $definition->options['templateData']);
        static::assertSame(['author', 'post'], array_column($this->probe->calls, 'name'));
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
            'incompatible inheritance' => [ChangedEntryKind::class, 'Cannot replace a field'],
        ];
    }

    #[DataProvider('invalidTrees')]
    public function testInvalidTreesAreRejected(string $class, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $this->factory->definition($class, $this->context());
    }

    public function testCycleFailureDoesNotPoisonLaterResolutions(): void
    {
        try {
            $this->factory->definition(CycleA::class, $this->context());
            static::fail('Expected a cycle to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString(CycleA::class, $invalidArgumentException->getMessage());
        }

        static::assertTrue($this->factory->definition(PostForm::class, $this->context())->has('author.email'));
    }

    public function testSeparateResolutionsKeepRemovalHistoryAndHookStateIsolated(): void
    {
        $restricted = $this->helper(attributes: ['canEditEmail' => false])->bind(definition: AuthorForm::class);
        $ordinary = $this->helper()->bind(definition: AuthorForm::class);
        static::assertNotNull($restricted->binding->definition);
        static::assertNotNull($ordinary->binding->definition);
        static::assertFalse($restricted->binding->definition->has('email'));
        static::assertTrue($ordinary->binding->definition->has('email'));
        static::assertSame(
            ['calls' => 1],
            $ordinary->binding->definition->field('first_name')->options['templateData'],
        );
    }

    public function testStandaloneCompilerExplainsMissingNestedResolver(): void
    {
        $this->expectException(LogicException::class);
        new BlueprintCompiler()->compile(new PostForm($this->probe), $this->context());
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
        $this->expectException(InvalidArgumentException::class);
        new Embed($path, $name);
    }

    public function testInvalidMountPathFailsBeforeRunningBlueprintHooks(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->definition(AuthorForm::class, at: 'author..name');
            static::fail('Expected invalid mounting to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('dot-separated', $invalidArgumentException->getMessage());
        }

        static::assertSame([], $this->probe->calls);
        $form->close();
    }
}
