<?php
declare(strict_types=1);

namespace FormCompose\Test;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\View\Form\ArrayContext;
use FormCompose\FormContext;

/**
 * Isolates real resolver metadata and plugin registrations for discovery tests.
 */
abstract class BlueprintTestCase extends FormTestCase
{
    /**
     * Isolate the discovered aliases from unrelated test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Blueprint/Valid/*.php'],
            'cache' => '_cake_attributes_',
            'validateFiles' => true,
        ]);
        AttributeResolver::clear('testBlueprints');
    }

    /**
     * Clear metadata and temporary plugin registrations after each test.
     */
    protected function tearDown(): void
    {
        AttributeResolver::clear('testBlueprints');
        AttributeResolver::drop('testBlueprints');
        Plugin::getCollection()->remove('BlueprintFixture');
        parent::tearDown();
    }

    /**
     * Construct one real binding context for factory-level failure and cache tests.
     */
    protected function context(): FormContext
    {
        return new FormContext(new ArrayContext([]), new ServerRequest());
    }
}
