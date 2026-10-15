<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use FormCompose\Field\FieldDefinition;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormDefinition;
use FormCompose\Test\Fixture\Blueprint\Invalid\AbstractForm;
use FormCompose\Test\Fixture\Blueprint\Invalid\DuplicateFields;
use FormCompose\Test\Fixture\Blueprint\Invalid\FailingForm;
use FormCompose\Test\Fixture\Blueprint\Invalid\InvalidPath;
use FormCompose\Test\Fixture\Blueprint\Valid\ConditionalForm;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Checks builder snapshots and blueprint failures before rendering begins.
 */
class BlueprintFailureTest extends FormTestCase
{
    public function testBuilderSnapshotsAreIndependentAndPreserveOrder(): void
    {
        $builder = new FormBuilder();
        $builder->add(FieldDefinition::make('name'))->add(FieldDefinition::make('email', 'email'));
        $original = $builder->build();
        $builder->with('email', ['label' => false, 'value' => null])->remove('name');
        static::assertSame(['name', 'email'], array_column($original->fields, 'path'));
        static::assertSame(['type' => 'email'], $original->field('email')->options);
        static::assertSame(['email'], array_column($builder->build()->fields, 'path'));
        static::assertSame(
            ['type' => 'email', 'label' => false, 'value' => null],
            $builder->build()->field('email')->options,
        );
    }

    public function testRemovingUnknownFieldsFailsClearly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FormBuilder()->remove('typo');
    }

    public function testDuplicateDeclarationsAreRejectedBeforeRendering(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate field paths');
        $this->helper()->bind(definition: DuplicateFields::class);
    }

    public function testInvalidDeclaredPathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind(definition: InvalidPath::class);
    }

    /**
     * Supply unrelated and abstract classes that must never become blueprints.
     *
     * @return array<string, array{string}>
     */
    public static function invalidClasses(): array
    {
        return [
            'unrelated' => [FormDefinition::class],
            'abstract' => [AbstractForm::class],
            'base' => [FormBlueprint::class],
        ];
    }

    #[DataProvider('invalidClasses')]
    public function testInvalidBlueprintClassesAreRejected(string $class): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('concrete implementation');
        $this->helper()->bind(definition: $class);
    }

    public function testDependencyFreeModeExplainsMissingContainerConfiguration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configure blueprintFactory');
        $this->helper()->bind(definition: ConditionalForm::class);
    }

    public function testHookFailuresPropagateWithoutLeakingConfiguration(): void
    {
        $helper = $this->helper();
        try {
            $helper->bind(definition: FailingForm::class);
            static::fail('Expected configuration to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Configuration failed.', $runtimeException->getMessage());
        }

        static::assertNull($helper->bind()->binding->definition);
        static::assertSame([], $helper->bind()->field('partial')->state->options);
    }

    public function testInvalidFactoryConfigurationIsRejected(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', false);
        $this->expectException(InvalidArgumentException::class);
        $helper->bind();
    }

    public function testDefinitionRenderingStillRequiresAnOpenForm(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()->bind(definition: ProfileForm::class)->definition();
    }
}
