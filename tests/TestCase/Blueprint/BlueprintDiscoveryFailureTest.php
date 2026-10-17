<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileChoices;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use InvalidArgumentException;
use RuntimeException;

/**
 * Rejects discovery collisions and failed construction with real collaborators.
 */
class BlueprintDiscoveryFailureTest extends BlueprintTestCase
{
    public function testUnknownAliasesFailClearly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown form blueprint "missing".');
        new BlueprintFactory(collection: 'testBlueprints')->compile('missing', $this->context());
    }

    public function testDuplicateFormAliasesFailDeterministically(): void
    {
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Collision/*.php'],
            'cache' => false,
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate discovery alias "collision".');
        new BlueprintFactory(collection: 'testBlueprints')->compile('collision', $this->context());
    }

    public function testDuplicateTypeAliasesAreRejected(): void
    {
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Collision/*.php'],
            'cache' => false,
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate discovery alias "collision-range".');
        new BlueprintFactory(collection: 'testBlueprints')->types();
    }

    public function testUnrelatedDiscoveredClassesCannotBecomeForms(): void
    {
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Invalid/NotAForm.php'],
            'cache' => false,
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('concrete implementation');
        new BlueprintFactory(collection: 'testBlueprints')->compile('invalid', $this->context());
    }

    public function testUnrelatedDiscoveredClassesCannotBecomeControls(): void
    {
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Invalid/NotAFieldType.php'],
            'cache' => false,
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('concrete implementation');
        new BlueprintFactory(collection: 'testBlueprints')->types();
    }

    public function testContainerCannotSubstituteAnUnrelatedInstance(): void
    {
        $container = new Container();
        $container->add(ProfileForm::class, new ProfileChoices());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return an instance');
        new BlueprintFactory($container, 'testBlueprints')->compile('profile', $this->context());
    }

    public function testDependencyConstructionFailuresPropagate(): void
    {
        $container = new Container();
        $container->add(ProfileForm::class, static function (): never {
            throw new RuntimeException('Dependency failed.');
        });
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dependency failed.');
        new BlueprintFactory($container, 'testBlueprints')->compile('profile', $this->context());
    }
}
