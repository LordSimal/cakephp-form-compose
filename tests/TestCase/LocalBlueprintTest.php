<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase;

use Cake\Container\Container;
use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\Authoring\SessionEmailForm;
use FormCompose\Test\Fixture\Blueprint\BlueprintFormHelper;
use FormCompose\Test\Fixture\Blueprint\Invalid\FailingForm;
use FormCompose\Test\Fixture\Blueprint\Valid\BillingAddressForm;
use FormCompose\Test\Fixture\Blueprint\Valid\ProfileForm;
use FormCompose\Test\Fixture\ContactFormHelper;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercises local blueprint compilation alongside session defaults and manual layouts.
 */
class LocalBlueprintTest extends BlueprintTestCase
{
    /**
     * Exercise both render-time identifiers against the real discovery collection.
     *
     * @return array<string, array{string}>
     */
    public static function identifiers(): array
    {
        return ['alias' => ['profile'], 'class' => [ProfileForm::class]];
    }

    #[DataProvider('identifiers')]
    public function testLocalBlueprintsUseBoundValuesWithoutInstallingDefaults(string $definition): void
    {
        $helper = $this->helper(['email' => 'posted@example.test']);
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->bind();
        $html = $form->open() . $form->blueprint($definition) . $form->close();
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('value="posted@example.test"', $html);
        static::assertStringContainsString('Full &lt;name&gt;', $html);
        static::assertStringContainsString('Use &lt;work&gt; email.', $html);
        static::assertNull($form->binding->definition);
        static::assertSame('email', $form->field('email')->state->type);
        static::assertSame([], $form->field('email')->state->options);
    }

    public function testMultipleDefinitionsShareOneSessionAndPreserveCallSiteOrder(): void
    {
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->bind(new ArrayContext([
            'data' => ['billing' => ['street' => 'Main Street', 'city' => 'Brussels']],
        ]));
        $html = $form->open();
        $html .= $form->blueprint('profile');
        $html .= '<section class="billing">' . $form->blueprint(BillingAddressForm::class) . '</section>';
        $html .= $form->control('notes') . $form->submit('Save') . $form->close();
        static::assertSame(1, substr_count($html, '<form '));
        static::assertSame(1, substr_count($html, '</form>'));
        static::assertStringContainsString('value="Main Street"', $html);
        static::assertStringContainsString('value="Brussels"', $html);
        static::assertStringContainsString('Your &lt;city&gt;', $html);
        static::assertLessThan(strpos($html, 'name="billing[street]"'), strpos($html, 'name="email"'));
        static::assertLessThan(strpos($html, 'name="notes"'), strpos($html, 'name="billing[city]"'));
        static::assertNull($form->binding->definition);
    }

    public function testLocalBlueprintsOverrideAndPreserveSelectedDefaults(): void
    {
        $form = $this->helper()->bind(blueprint: SessionEmailForm::class);
        $defaults = $form->binding->definition;
        $html = $form->open() . $form->blueprint(ProfileForm::class);
        static::assertStringContainsString('type="email"', $html);
        static::assertStringContainsString('Use &lt;work&gt; email.', $html);
        static::assertStringNotContainsString('Session email', $html);
        static::assertStringNotContainsString('name="unrendered"', $html);
        static::assertSame($defaults, $form->binding->definition);
        static::assertSame('Session email', $form->field('email')->state->options['label']);
        static::assertSame('text', $form->field('email')->state->type);
        $form->close();
    }

    public function testInjectedFactoryWorksThroughSessionSubclassesAndConditionalHooks(): void
    {
        $helper = new ContactFormHelper($this->helper(attributes: ['canManageAccounts' => true])->getView());
        $helper->setConfig('blueprintFactory', new BlueprintFactory(new Container(), 'testBlueprints'));

        $form = $helper->bind(new ArrayContext(['data' => ['label' => 'Runtime label']]));
        $html = $form->open() . $form->blueprint('conditional') . $form->close();
        static::assertStringContainsString('Runtime label', $html);
        static::assertStringContainsString('Work &lt;address&gt;', $html);
        static::assertStringContainsString('name="active"', $html);
        static::assertStringNotContainsString('name="internal_note"', $html);
        static::assertStringContainsString('data-contact-label', $html);
        static::assertNull($form->binding->definition);
    }

    public function testHelperFactoryOverridesAreRetainedForLocalCompilation(): void
    {
        $helper = new BlueprintFormHelper($this->helper()->getView());
        $form = $helper->bind();
        $html = $form->open() . $form->blueprint(ProfileForm::class) . $form->close();
        static::assertStringContainsString('Custom builder', $html);
        static::assertStringContainsString('name="custom"', $html);
        static::assertNull($form->binding->definition);
    }

    public function testLocalCompilationRequiresAnOpenSessionBeforeCallingHooks(): void
    {
        $form = $this->helper()->bind();
        $this->expectException(LogicException::class);
        $form->blueprint(FailingForm::class);
    }

    public function testLocalCompilationCannotRunAfterClosing(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->close();
        $this->expectException(LogicException::class);
        $form->blueprint(FailingForm::class);
    }

    public function testUnknownLocalAliasesDoNotInstallDefaults(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->blueprint('missing');
            static::fail('Expected unknown local blueprint to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('Unknown form blueprint', $invalidArgumentException->getMessage());
        }

        static::assertNull($form->binding->definition);
        static::assertStringContainsString('name="ordinary"', $form->control('ordinary'));
        $form->close();
    }

    public function testOverlappingDefinitionsRetainDuplicateInputProtection(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->blueprint(ProfileForm::class);
        $this->expectException(LogicException::class);
        $form->blueprint(ProfileForm::class);
    }
}
