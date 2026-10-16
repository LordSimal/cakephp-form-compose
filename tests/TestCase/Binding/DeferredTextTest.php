<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use ArrayObject;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\View\Form\ArrayContext;
use Closure;
use FormCompose\Rendering\Html;
use FormCompose\Test\Fixture\Translation\ArticleForm;
use FormCompose\Test\Fixture\Translation\OverriddenForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Verifies deferred text timing, translation, escaping, and field-state consistency.
 */
class DeferredTextTest extends FormTestCase
{
    public function testAttributeClosuresUseTheLocaleAtFieldResolutionAndKeepASnapshot(): void
    {
        $locale = I18n::getLocale();
        I18n::setTranslator(
            'form-compose-tests',
            static fn(): Package => new Package(messages: [
                'Article title' => 'Titre <public>',
                'Shown publicly' => 'Visible <partout>',
            ]),
            'fr_FR',
        );
        I18n::setTranslator(
            'form-compose-tests',
            static fn(): Package => new Package(),
            'en_US',
        );
        try {
            I18n::setLocale('en_US');
            $form = $this->helper()->bind(new ArrayContext([
                'errors' => ['title' => ['invalid' => 'Invalid title']],
            ]), definition: ArticleForm::class);
            static::assertInstanceOf(Closure::class, $form->binding->definition?->field('title')->options['label']);
            I18n::setLocale('fr_FR');
            $field = $form->field('title');
            static::assertSame('Titre <public>', $field->state->options['label']);
            static::assertSame('Visible <partout>', $field->state->options['help']);
            I18n::setLocale('en_US');
            static::assertSame('Article title', $form->field('title')->state->options['label']);
            static::assertSame('Titre &lt;public&gt;', $field->state->labelText());
            $form->open();
            static::assertSame('', $form->errorSummary());
            $html = $field->control();
            static::assertStringContainsString('Titre &lt;public&gt;', $html);
            static::assertStringContainsString('Visible &lt;partout&gt;', $html);
            static::assertStringContainsString(
                'aria-describedby="form-compose-1-title-help form-compose-1-title-errors"',
                $html,
            );
            static::assertStringContainsString('Titre &lt;public&gt;: Invalid title', $form->errorSummary());
            $form->close();
        } finally {
            I18n::clear();
            I18n::setLocale($locale);
        }
    }

    public function testInlineCallbacksRunOncePerFieldAndOtherOptionsRemainUntouched(): void
    {
        $calls = new ArrayObject(['label' => 0, 'help' => 0]);
        $other = static fn(): string => 'Application callback';
        $form = $this->helper()->bind();
        $field = $form->field('title', [
            'label' => static function () use ($calls): Html {
                $calls['label']++;

                return new Html('<em>Title</em>');
            },
            'help' => static function () use ($calls): Html {
                $calls['help']++;

                return new Html('<strong>Help</strong>');
            },
            'templateData' => ['callback' => $other],
        ]);
        $field->attributes();
        $field->state->labelText();
        $field->state->helpText();

        $html = $form->open() . $field->control() . $form->close();
        static::assertSame(['label' => 1, 'help' => 1], $calls->getArrayCopy());
        static::assertSame(['callback' => $other], $field->state->options['templateData']);
        static::assertStringContainsString('<em>Title</em>', $html);
        static::assertStringContainsString('<strong>Help</strong>', $html);
    }

    public function testPerCallOptionsReplaceBlueprintClosuresBeforeTheyExecute(): void
    {
        $form = $this->helper()->bind(definition: OverriddenForm::class);
        $html = $form->open() . $form->control('title', ['label' => false, 'help' => null]) . $form->close();
        static::assertStringNotContainsString('<label', $html);
        static::assertStringNotContainsString('title-help', $html);
        static::assertInstanceOf(Closure::class, $form->binding->definition?->field('title')->options['label']);
    }

    /**
     * @param string|false|null $text Deferred presentation value.
     */
    #[DataProvider('suppressedText')]
    public function testEmptyCallbackResultsPreserveLabelAndHelpSemantics(string|false|null $text): void
    {
        $form = $this->helper()->bind();
        $field = $form->field('article_title', [
            'label' => static fn(): string|false|null => $text,
            'help' => static fn(): string|false|null => $text,
        ]);
        static::assertFalse($field->state->hasHelp());
        static::assertSame($text, $field->state->options['label']);
        static::assertSame($text === null ? 'Article Title' : '', $field->state->labelText());
        $html = $form->open() . $field->control() . $form->close();
        static::assertStringNotContainsString('aria-describedby', $html);
        if ($text === false) {
            static::assertStringNotContainsString('<label', $html);
        }
    }

    /**
     * @return array<string, array{string|false|null}>
     */
    public static function suppressedText(): array
    {
        return ['false' => [false], 'null' => [null], 'blank' => ['']];
    }

    public function testCallbackExceptionsPropagateWithoutRegisteringInputs(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('title', ['label' => static function (): never {
                throw new RuntimeException('Translation failed.');
            }]);
            static::fail('Expected the callback failure.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Translation failed.', $runtimeException->getMessage());
        }

        static::assertStringContainsString('name="title"', $form->control('title'));
        $form->close();
    }

    /**
     * @param mixed $result Unsupported deferred text value.
     */
    #[DataProvider('invalidResults')]
    public function testUnsupportedCallbackResultsFailDuringResolution(string $key, mixed $result): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The ' . $key . ' closure must return a string, Html, false, or null.');
        $this->helper()
            ->bind()
            ->field('title', [$key => static fn(): mixed => $result]);
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function invalidResults(): array
    {
        return [
            'integer label' => ['label', 42],
            'boolean help' => ['help', true],
            'array label' => ['label', ['Title']],
            'nested callback' => ['help', static fn(): string => 'Help'],
        ];
    }
}
