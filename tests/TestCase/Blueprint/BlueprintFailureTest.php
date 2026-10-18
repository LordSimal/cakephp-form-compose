<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Test\Fixture\Authoring\UnknownRemovalForm;
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
        $factory = new BlueprintFactory();
        $context = new FormContext(new ArrayContext([]), $this->helper()->getView()->getRequest());
        $builder = new BlueprintBuilder(
            new FormDefinition([]),
            static fn(string $path, string $identifier): FormDefinition => $factory->compile(
                $identifier,
                $context->at($path),
            ),
            static fn(RepeatedDefinition $entry): RepeatedDefinition => $factory->repeated($entry, $context),
        );
        $builder->add('name');
        $builder->add('email')->type('email');
        $original = $builder->build();
        $builder->field('email')->label(false)->value(null);
        $builder->remove('name');
        $changed = $builder->build();
        static::assertSame(['name', 'email'], array_column($original->fields, 'path'));
        static::assertSame('email', $original->field('email')->config->type);
        static::assertNull($original->field('email')->config->label);
        static::assertFalse($original->field('email')->config->hasValue);
        static::assertSame(['email'], array_column($changed->fields, 'path'));
        static::assertSame('email', $changed->field('email')->config->type);
        static::assertFalse($changed->field('email')->config->label);
        static::assertTrue($changed->field('email')->config->hasValue);
        static::assertNull($changed->field('email')->config->value);
    }

    public function testRemovingUnknownFieldsFailsClearly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->form()->blueprint(UnknownRemovalForm::class)->bind();
    }

    public function testDuplicateDeclarationsAreRejectedBeforeRendering(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate field paths');
        $this->helper()->form()->blueprint(DuplicateFields::class)->bind();
    }

    public function testInvalidDeclaredPathsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->form()->blueprint(InvalidPath::class)->bind();
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
        $this->helper()->form()->blueprint($class)->bind();
    }

    public function testDependencyFreeModeExplainsMissingContainerConfiguration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configure blueprintFactory');
        $this->helper()->form()->blueprint(ConditionalForm::class)->bind();
    }

    public function testHookFailuresPropagateWithoutLeakingConfiguration(): void
    {
        $helper = $this->helper();
        try {
            $helper->form()->blueprint(FailingForm::class)->bind();
            static::fail('Expected configuration to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Configuration failed.', $runtimeException->getMessage());
        }

        static::assertNull($helper->form()->bind()->binding->definition);
        static::assertNull($helper->form()->bind()->field('partial')->resolve()->state->config->type);
    }

    public function testInvalidFactoryConfigurationIsRejected(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', false);
        $this->expectException(InvalidArgumentException::class);
        $helper->form()->bind();
    }

    public function testDefinitionRenderingStillRequiresAnOpenForm(): void
    {
        $this->expectException(LogicException::class);
        $this->helper()
            ->form()
            ->blueprint(ProfileForm::class)
            ->bind()
            ->blueprint()
            ->render();
    }
}
