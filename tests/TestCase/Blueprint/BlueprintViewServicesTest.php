<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Controller\Controller;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\View\View;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\FormComposePlugin;
use FormCompose\Test\Fixture\Blueprint\CustomBlueprintFactory;
use FormCompose\Test\Fixture\Blueprint\Lazy\TypeProbe;
use FormCompose\Test\Fixture\Blueprint\Valid\ConditionalForm;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileChoices;
use FormCompose\Test\Fixture\BlueprintApplication;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Exercises container construction through CakePHP's real controller/view/helper lifecycle.
 */
class BlueprintViewServicesTest extends FormTestCase
{
    protected EventManager $originalEvents;

    protected PluginCollection $originalPlugins;

    /** @var array<string, mixed> */
    protected array $originalDiscovery;

    /** Isolate each application's listener and discovery registrations. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEvents = EventManager::instance();
        $this->originalPlugins = Plugin::getCollection();
        EventManager::instance(new EventManager());
        /** @var array<string, mixed> $discovery */
        $discovery = AttributeResolver::getConfig('formCompose');
        $this->originalDiscovery = $discovery;
        AttributeResolver::drop('formCompose');
        AttributeResolver::setConfig('formCompose', [
            'paths' => ['tests/Fixture/Blueprint/Valid/*.php', 'tests/Fixture/Blueprint/Lazy/*.php'],
            'cache' => false,
        ]);
    }

    /** Restore the host application after each isolated render. */
    protected function tearDown(): void
    {
        AttributeResolver::drop('formCompose');
        AttributeResolver::setConfig('formCompose', $this->originalDiscovery);
        EventManager::instance($this->originalEvents);
        Plugin::setCollection($this->originalPlugins);
        parent::tearDown();
    }

    /** @return array<string, array{string, bool}> */
    public static function blueprintSelections(): array
    {
        return [
            'bound alias' => ['conditional', false],
            'bound class' => [ConditionalForm::class, false],
            'local alias' => ['conditional', true],
            'local class' => [ConditionalForm::class, true],
        ];
    }

    /**
     * @param string $blueprint Selected alias or explicit class.
     * @param bool $local Whether the blueprint is selected after binding.
     */
    #[DataProvider('blueprintSelections')]
    public function testControllerViewsAutowireBlueprintAndFieldTypeServices(string $blueprint, bool $local): void
    {
        $app = $this->application();
        $probe = new TypeProbe();
        $app->getContainer()->addShared(TypeProbe::class, $probe);
        $app->pluginBootstrap();
        static::assertSame(0, $probe->count());

        $controller = $this->controller($blueprint, $local);
        $controller->set('customType', true);

        $html = (string)$controller->render()->getBody();
        static::assertStringContainsString('Work &lt;address&gt;', $html);
        static::assertStringContainsString('name="custom"', $html);
        static::assertSame(1, $probe->count());
        static::assertSame(
            $app->getContainer()->get(BlueprintFactory::class),
            $controller->viewBuilder()->getOption('formCompose.blueprintFactory'),
        );
        static::assertArrayNotHasKey('blueprints', $controller->viewBuilder()->getVars());

        // A second controller gets fresh field types and the current request's conditions.
        $second = $this->controller($blueprint, $local);
        $second->setRequest($second->getRequest()->withAttribute('canManageAccounts', true));
        $second->set('customType', true);

        $secondHtml = (string)$second->render()->getBody();
        static::assertStringNotContainsString('name="active"', $html);
        static::assertStringContainsString('name="active"', $secondHtml);
        static::assertSame(2, $probe->count());
    }

    public function testApplicationFactoryOverridesReachNormalViews(): void
    {
        $app = $this->application();
        $factory = new CustomBlueprintFactory($app->getContainer());
        $app->getContainer()->addShared(BlueprintFactory::class, $factory);
        $app->pluginBootstrap();

        $html = (string)$this->controller()->render()->getBody();
        static::assertStringContainsString('Custom builder', $html);
    }

    public function testApplicationServiceFailuresPropagateThroughControllerRendering(): void
    {
        $app = $this->application();
        $app->getContainer()->add(ProfileChoices::class, static function (): ProfileChoices {
            throw new RuntimeException('Choice service unavailable.');
        });
        $app->pluginBootstrap();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Choice service unavailable.');
        $this->controller()->render();
    }

    public function testExplicitViewFactoryTakesPrecedenceOverApplicationFactory(): void
    {
        $app = $this->application();
        $app->pluginBootstrap();

        $factory = new CustomBlueprintFactory($app->getContainer());
        $controller = $this->controller();
        $controller->viewBuilder()->setOption('formCompose.blueprintFactory', $factory);

        static::assertStringContainsString('Custom builder', (string)$controller->render()->getBody());
        static::assertSame($factory, $controller->viewBuilder()->getOption('formCompose.blueprintFactory'));
    }

    public function testExplicitHelperFactoryTakesPrecedenceOverViewFactory(): void
    {
        $view = new View();
        $view->loadHelper('Form', ['className' => 'FormCompose.Form']);
        $view->setConfig('formCompose.blueprintFactory', false);

        $helper = $view->helpers()->get('Form');
        static::assertInstanceOf(FormHelper::class, $helper);
        $helper->setConfig('blueprintFactory', new CustomBlueprintFactory($this->application()->getContainer()));

        static::assertStringContainsString(
            'Custom builder',
            $helper->form()->blueprint(ConditionalForm::class)->render(),
        );
    }

    public function testInvalidViewFactoryFailsInsteadOfSilentlyLosingServices(): void
    {
        $view = new View();
        $view->loadHelper('Form', ['className' => 'FormCompose.Form']);
        $view->setConfig('formCompose.blueprintFactory', false);

        $helper = $view->helpers()->get('Form');
        static::assertInstanceOf(FormHelper::class, $helper);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('blueprintFactory must be a BlueprintFactory instance.');
        $helper->form()->bind();
    }

    public function testListenerIgnoresEventsWithoutAController(): void
    {
        $this->application()->pluginBootstrap();
        $event = new Event('Controller.beforeRender');
        EventManager::instance()->dispatch($event);
        static::assertFalse($event->isStopped());
        static::assertNull($event->getResult());
    }

    public function testDisabledPluginEventsLeaveStandaloneFactoryBehavior(): void
    {
        $this->application(events: false)->pluginBootstrap();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Configure blueprintFactory');
        $this->controller()->render();
    }

    /** Create a real application with plugin services before listener registration. */
    protected function application(bool $events = true): BlueprintApplication
    {
        $app = new BlueprintApplication(CONFIG);
        $app->addPlugin(new FormComposePlugin(['path' => dirname(__DIR__, 3) . '/', 'events' => $events]));

        return $app;
    }

    /** Create a conventional controller with no injected factory or factory view variable. */
    protected function controller(string $blueprint = 'conditional', bool $local = false): Controller
    {
        $controller = new Controller(new ServerRequest(['params' => [
            'controller' => 'Blueprint',
            'action' => 'services',
        ]]));
        $controller->viewBuilder()
            ->setClassName(View::class)
            ->setHelpers(['Form' => ['className' => 'FormCompose.Form']])
            ->setTemplatePath('Blueprint')
            ->setTemplate('services')
            ->disableAutoLayout();
        $controller->set(['blueprint' => $blueprint, 'local' => $local, 'customType' => false]);

        return $controller;
    }
}
