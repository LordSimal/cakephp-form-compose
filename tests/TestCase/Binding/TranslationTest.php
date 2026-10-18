<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\View\Form\ArrayContext;
use Closure;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Test\Fixture\Translation\MessageForm;
use FormCompose\Test\FormTestCase;
use RuntimeException;
use function Cake\I18n\__;
use function Cake\I18n\__d;

/**
 * Checks CakePHP translations in blueprint hooks and deferred callbacks through binding and rendering.
 */
class TranslationTest extends FormTestCase
{
    protected string $locale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locale = I18n::getLocale();
        I18n::clear();
        Cache::clear('_cake_translations_');
        I18n::setTranslator(
            'form-compose-tests',
            static fn(): Package => new Package(messages: [
                'Article title {0}' => ['_context' => ['heading' => 'Titre {0}', '' => 'Wrong context {0}']],
                'Shown publicly' => 'Visible <partout>',
            ]),
            'fr_FR',
        );
        I18n::setTranslator('form-compose-tests', static fn(): Package => new Package(), 'en_US');
    }

    protected function tearDown(): void
    {
        I18n::clear();
        Cache::clear('_cake_translations_');
        I18n::setLocale($this->locale);
        parent::tearDown();
    }

    public function testBlueprintHooksTranslateAtBindingAndRetainTheirLocaleSnapshot(): void
    {
        I18n::setLocale('fr_FR');
        $form = $this->helper()
            ->form(new ArrayContext([
                'errors' => ['title' => ['invalid' => 'Invalid title']],
            ]))
            ->blueprint(MessageForm::class)
            ->bind();
        /** @var mixed $label */
        $label = $form->binding->definition?->field('title')->config->label;
        static::assertSame('Titre <public>', $label);
        $field = $form->field('title')->resolve();
        static::assertSame('Titre <public>', $field->state->config->label);
        static::assertSame('Visible <partout>', $field->state->config->help);
        I18n::setLocale('en_US');
        static::assertSame('Titre <public>', $form->field('title')->resolve()->state->config->label);
        $english = $this->helper()->form()->blueprint(MessageForm::class)->bind();
        static::assertSame('Article title <public>', $english->field('title')->resolve()->state->config->label);
        static::assertSame('Titre &lt;public&gt;', $field->state->labelText());
        $form->open();
        static::assertSame('', $form->errorSummary()->render());
        $html = $field->control();
        static::assertStringContainsString('Visible &lt;partout&gt;', $html);
        static::assertStringContainsString(
            'aria-describedby="form-compose-1-title-help form-compose-1-title-errors"',
            $html,
        );
        static::assertStringContainsString('Titre &lt;public&gt;: Invalid title', $form->errorSummary()->render());
        static::assertStringNotContainsString('<public>', $html);
        $form->close();
        static::assertSame($label, $form->binding->definition?->field('title')->config->label);
    }

    public function testDefaultDomainAndNamedCakeFormattingArguments(): void
    {
        I18n::setLocale('fr_FR');
        I18n::setTranslator(
            'default',
            static fn(): Package => new Package(messages: [
                'Title for {name}' => 'Titre pour {name}',
            ]),
            'fr_FR',
        );
        $message = __('Title for {name}', ['name' => '<Ada>']);
        $form = $this->helper()->form()->bind();
        $html =
            $form->open()
            . $form->control('title')->label($message)->render()
            . $form->close();
        static::assertStringContainsString('Titre pour &lt;Ada&gt;', $html);
    }

    public function testClosuresMayComputeMessagesWhileLiteralStringsRemainLiteral(): void
    {
        I18n::setLocale('fr_FR');
        $form = $this->helper()->form()->bind();
        $field = $form->field('title')
            ->label(static fn(): string => __d('form-compose-tests', 'Shown publicly'))
            ->help('Shown publicly')
            ->resolve();
        static::assertSame('Visible <partout>', $field->state->config->label);
        static::assertSame('Shown publicly', $field->state->config->help);
    }

    public function testPerCallOverridesPreventTranslationOfDiscardedConfigCallbacks(): void
    {
        I18n::setLocale('fr_FR');
        I18n::setTranslator(
            'form-compose-tests',
            static fn(): never => throw new RuntimeException('Not needed.'),
            'fr_FR',
        );
        $settings = FieldConfig::builder()
            ->label(static fn(): string => __d('form-compose-tests', 'Article title'))
            ->help(static fn(): string => __d('form-compose-tests', 'Shown publicly'));
        $form = $this->helper()->form()->bind();
        $field = $form->field('title', config: $settings)->label(false)->help(null)->resolve();
        static::assertFalse($field->state->config->label);
        static::assertFalse($field->state->hasHelp());
        static::assertInstanceOf(Closure::class, $settings->label);
    }

    public function testTranslationFailuresPropagateBeforeInputRegistration(): void
    {
        I18n::setLocale('fr_FR');
        I18n::setTranslator(
            'failing-translation',
            static fn(): never => throw new RuntimeException('Translation failed.'),
            'fr_FR',
        );
        $form = $this->helper()->form()->bind();
        $form->open();
        try {
            $form->control('title')
                ->label(static fn(): string => __d('failing-translation', 'Title'))
                ->render();
            static::fail('Expected translation to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Translation failed.', $runtimeException->getMessage());
        }

        static::assertStringContainsString('name="title"', $form->control('title')->render());
        $form->close();
    }
}
