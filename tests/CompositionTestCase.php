<?php
declare(strict_types=1);

namespace FormCompose\Test;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Container\Container;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Test\Fixture\Composition\CompositionProbe;
use FormCompose\View\Helper\FormHelper;

/**
 * Shares real discovery and container services for nested blueprint integration tests.
 */
abstract class CompositionTestCase extends BlueprintTestCase
{
    protected BlueprintFactory $factory;

    protected CompositionProbe $probe;

    /**
     * Isolate composition aliases and record per-occurrence hook contexts.
     */
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Valid/*.php', 'tests/Fixture/Composition/*.php'],
            'cache' => '_cake_core_',
            'validateFiles' => true,
        ]);
        AttributeResolver::clear('testBlueprints');
        $this->probe = new CompositionProbe();
        $container = new Container();
        $container->addShared(CompositionProbe::class, $this->probe);

        $this->factory = new BlueprintFactory($container, 'testBlueprints');
    }

    /**
     * Keep nested aliases and injected services available through ordinary helper binding.
     *
     * @param array<string, mixed> $data Submitted body.
     * @param array<string, mixed> $attributes Request attributes.
     */
    protected function helper(array $data = [], array $attributes = []): FormHelper
    {
        $helper = parent::helper($data, $attributes);
        $helper->setConfig('blueprintFactory', $this->factory);

        return $helper;
    }
}
