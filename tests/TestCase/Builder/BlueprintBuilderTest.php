<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Builder;

use ArrayObject;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\EmbeddedDefinition;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Builder\RepeatedBlueprintBuilder;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\FieldDefinition;
use PHPUnit\Framework\TestCase;

/** Preserves compiled composition when builders specialize or replace declarations. */
class BlueprintBuilderTest extends TestCase
{
    public function testCompiledSeedsKeepNestedStructurePresentationAndOmissions(): void
    {
        $child = new FormDefinition([new FieldDefinition('email'), new FieldDefinition('obsolete')])->remove(
            'obsolete',
        );
        $seed = new FormDefinition([])->embed('author', $child);
        $builder = $this->builder($seed);
        $builder->field('author.email')->label('Contact email');
        $compiled = $builder->build();

        static::assertInstanceOf(EmbeddedDefinition::class, $compiled->entries[0]);
        static::assertSame('Contact email', $compiled->embedded('author')->field('email')->config->label);
        static::assertTrue($compiled->index->wasRemoved('author.obsolete'));
        static::assertNull($seed->field('author.email')->config->label);
    }

    public function testInheritedDeclarationsCanBeIntentionallyReplacedWithoutMovingThem(): void
    {
        $builder = $this->builder(new FormDefinition([new FieldDefinition('title'), new FieldDefinition('body')]));
        $builder->remove('title')->add('title', new FieldConfig(type: 'textarea', label: 'New title'));
        $compiled = $builder->build();

        static::assertSame(['title', 'body'], $compiled->index->paths);
        static::assertSame('textarea', $compiled->field('title')->config->type);
        static::assertFalse($compiled->index->wasRemoved('title'));
    }

    public function testRemovedDeferredEmbedsDoNotCompileTheirChildren(): void
    {
        $builder = new BlueprintBuilder(
            new FormDefinition([]),
            static function (string $path, string $blueprint): FormDefinition {
                static::fail('A removed embed must not construct or configure its child.');
            },
            static fn(RepeatedDefinition $entry): RepeatedDefinition => $entry,
        );
        $builder->embed('author', 'author');
        $builder->remove('author');

        $compiled = $builder->build();

        static::assertSame([], $compiled->entries);
        static::assertTrue($compiled->index->wasRemoved('author.email'));
        static::assertFalse($compiled->has('author'));
    }

    public function testSingleEmbedPresentationEditsReuseTheCompiledChild(): void
    {
        /** @var ArrayObject<int, string> $calls */
        $calls = new ArrayObject();
        $builder = new BlueprintBuilder(
            new FormDefinition([]),
            static function (string $path, string $blueprint) use ($calls): FormDefinition {
                $calls->append($path);

                return new FormDefinition([new FieldDefinition('email')]);
            },
            static fn(RepeatedDefinition $entry): RepeatedDefinition => $entry,
        );
        $embed = $builder->embed('author', 'author')->templateData(['density' => 'normal']);
        $first = $builder->build();
        $embed->templateData(['density' => 'compact']);
        $second = $builder->build();
        static::assertCount(1, $calls);
        static::assertSame('normal', $first->index->embedPresentation['author']->templateData['density']);
        static::assertSame('compact', $second->index->embedPresentation['author']->templateData['density']);
    }

    public function testRepeatedBuildersRecompileOnlyWhenTheSelectedKeysChange(): void
    {
        /** @var ArrayObject<int, list<int|string>|null> $selections */
        $selections = new ArrayObject();
        $builder = new RepeatedBlueprintBuilder('comments', static function (?array $keys) use (
            $selections,
        ): RepeatedDefinition {
            /** @var list<int|string>|null $keys */
            $selections->append($keys);

            return new RepeatedDefinition('comments', 'comment', $keys);
        });
        $first = $builder->keys([2])->label('Original')->build();
        $second = $builder->label('Edited')->build();
        static::assertCount(1, $selections);
        static::assertSame('Original', $first->config->label);
        static::assertSame('Edited', $second->config->label);
        $third = $builder->keys([7])->build();
        static::assertCount(2, $selections);
        static::assertSame([2], $first->keys);
        static::assertSame([7], $third->keys);
    }

    /** Create a seeded builder whose existing child trees need no resolver calls. */
    protected function builder(FormDefinition $definition): BlueprintBuilder
    {
        return new BlueprintBuilder(
            $definition,
            static fn(string $path, string $blueprint): FormDefinition => new FormDefinition([]),
            static fn(RepeatedDefinition $entry): RepeatedDefinition => $entry,
        );
    }
}
