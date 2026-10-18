<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/** Checks shared native button state through standalone protected actions and immutable snapshots. */
class PostButtonConfigurationTest extends FormTestCase
{
    /** Preserve zero, false, empty strings, and null through the typed action boundary. */
    #[DataProvider('buttonValues')]
    public function testTypedButtonValuesMatchOrdinaryButtons(mixed $value, string $expected): void
    {
        $helper = $this->helper();
        $settings = ActionConfig::builder()->name('intent')->value($value)->disabled();
        $action = $helper->postButton('Save <record>', '/save', config: $settings);
        $settings->name('changed')->value('changed')->disabled(false);
        $html = $action->render();
        $xpath = $this->xpath($html);
        static::assertSame('intent', $xpath->evaluate('string(//button/@name)'));
        static::assertSame($expected, $xpath->evaluate('string(//button/@value)'));
        static::assertSame(1.0, $xpath->evaluate('count(//button[@disabled])'));
        static::assertStringContainsString('Save &lt;record&gt;', $html);

        $form = $helper->form()->bind();
        $ordinary =
            $form->open() . $form->submit('Save')->name('intent')->value($value)->disabled()->render() . $form->close();
        static::assertSame($expected, $this->xpath($ordinary)->evaluate('string(//button/@value)'));
    }

    /** @return array<string, array{mixed, string}> */
    public static function buttonValues(): array
    {
        return [
            'zero' => [0, '0'],
            'false' => [false, ''],
            'empty' => ['', ''],
            'null' => [null, ''],
            'escaping' => ['<save & continue>', '<save & continue>'],
        ];
    }

    public function testFluentOverridesResetPreviouslyConfiguredButtonState(): void
    {
        $settings = ActionConfig::builder()->name('old')->value('old')->disabled();
        $action = $this->helper()
            ->postButton('Save', '/save', config: $settings)
            ->name(null)
            ->value(0)
            ->disabled(false);
        $html = $action->render();
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//button/@name | //button/@disabled)'));
        static::assertSame('0', $this->xpath($html)->evaluate('string(//button/@value)'));
        static::assertSame('old', $settings->config()->name);
        static::assertTrue($settings->config()->disabled);
    }

    public function testLinksExposeOnlyApplicableMethods(): void
    {
        $link = $this->helper()->postLink('Archive', '/archive');
        static::assertFalse(method_exists($link, 'name'));
        static::assertFalse(method_exists($link, 'value'));
        static::assertFalse(method_exists($link, 'disabled'));
        static::assertStringContainsString('<a', $link->render());
    }

    /** Reject mismatched reusable settings before writing a view block. */
    #[DataProvider('buttonOnlySettings')]
    public function testLinksRejectButtonOnlyConfiguration(ActionConfig $config): void
    {
        $helper = $this->helper();
        $this->expectException(InvalidArgumentException::class);
        try {
            $helper->postLink('Archive', '/archive', config: $config)->block('actions')->render();
        } finally {
            static::assertSame('', $helper->getView()->fetch('actions'));
        }
    }

    /** @return array<string, array{ActionConfig}> */
    public static function buttonOnlySettings(): array
    {
        return [
            'name' => [new ActionConfig()->withName('intent')],
            'value' => [new ActionConfig()->withValue(0)],
            'disabled' => [new ActionConfig()->withDisabled(true)],
        ];
    }
}
