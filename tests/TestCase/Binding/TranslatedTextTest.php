<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\Translation\MessageForm;
use FormCompose\Test\FormTestCase;
use FormCompose\Translated;
use RuntimeException;

/**
 * Checks declarative messages through ordinary binding, part rendering, and summaries.
 */
class TranslatedTextTest extends FormTestCase
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

    public function testAttributesTranslateOnResolutionAndRetainTheirLocaleSnapshot(): void
    {
        I18n::setLocale('en_US');
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['title' => ['invalid' => 'Invalid title']],
        ]), blueprint: MessageForm::class);
        /** @var mixed $label */
        $label = $form->binding->definition?->field('title')->options['label'];
        static::assertInstanceOf(Translated::class, $label);
        static::assertSame('Article title {0}', $label->message);
        I18n::setLocale('fr_FR');
        $field = $form->field('title');
        static::assertSame('Titre <public>', $field->state->options['label']);
        static::assertSame('Visible <partout>', $field->state->options['help']);
        I18n::setLocale('en_US');
        static::assertSame('Article title <public>', $form->field('title')->state->options['label']);
        static::assertSame('Titre &lt;public&gt;', $field->state->labelText());
        $form->open();
        static::assertSame('', $form->errorSummary());
        $html = $field->control();
        static::assertStringContainsString('Visible &lt;partout&gt;', $html);
        static::assertStringContainsString(
            'aria-describedby="form-compose-1-title-help form-compose-1-title-errors"',
            $html,
        );
        static::assertStringContainsString('Titre &lt;public&gt;: Invalid title', $form->errorSummary());
        static::assertStringNotContainsString('<public>', $html);
        $form->close();
        static::assertSame($label, $form->binding->definition?->field('title')->options['label']);
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
        $message = new Translated('Title for {name}', ['name' => '<Ada>']);
        $form = $this->helper()->bind();
        $html = $form->open() . $form->control('title', ['label' => $message]) . $form->close();
        static::assertStringContainsString('Titre pour &lt;Ada&gt;', $html);
        static::assertSame('', new Translated('')->translate());
    }

    public function testClosuresMayComputeMessagesWhileLiteralStringsRemainLiteral(): void
    {
        I18n::setLocale('fr_FR');
        $form = $this->helper()->bind();
        $field = $form->field('title', [
            'label' => static fn(): Translated => new Translated('Shown publicly', domain: 'form-compose-tests'),
            'help' => 'Shown publicly',
        ]);
        static::assertSame('Visible <partout>', $field->state->options['label']);
        static::assertSame('Shown publicly', $field->state->options['help']);
    }

    public function testPerCallOverridesPreventTranslationOfDiscardedBlueprintMessages(): void
    {
        I18n::setLocale('fr_FR');
        I18n::setTranslator(
            'form-compose-tests',
            static fn(): never => throw new RuntimeException('Not needed.'),
            'fr_FR',
        );
        $form = $this->helper()->bind(blueprint: MessageForm::class);
        $field = $form->field('title', ['label' => false, 'help' => null]);
        static::assertFalse($field->state->options['label']);
        static::assertFalse($field->state->hasHelp());
        static::assertInstanceOf(Translated::class, $form->binding->definition?->field('title')->options['label']);
    }

    public function testTranslationFailuresPropagateBeforeInputRegistration(): void
    {
        I18n::setLocale('fr_FR');
        I18n::setTranslator(
            'failing-translation',
            static fn(): never => throw new RuntimeException('Translation failed.'),
            'fr_FR',
        );
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('title', ['label' => new Translated('Title', domain: 'failing-translation')]);
            static::fail('Expected translation to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Translation failed.', $runtimeException->getMessage());
        }

        static::assertStringContainsString('name="title"', $form->control('title'));
        $form->close();
    }
}
