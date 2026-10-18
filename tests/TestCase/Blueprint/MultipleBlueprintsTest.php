<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\Form\FormProtector;
use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Test\CompositionTestCase;
use FormCompose\Test\Fixture\Authoring\CommentsForm;
use FormCompose\Test\Fixture\Authoring\EmailForm;
use FormCompose\Test\Fixture\Authoring\FailingPostForm;
use FormCompose\Test\Fixture\Authoring\TitleForm;
use FormCompose\Test\Fixture\Authoring\TitleIdForm;
use FormCompose\Test\Fixture\Composition\AuthorForm;
use FormCompose\Test\Fixture\Composition\PostForm;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/** Exercises ordered blueprint selection through binding, layouts, and one protected form lifecycle. */
class MultipleBlueprintsTest extends CompositionTestCase
{
    public function testCompleteFormsPreserveEveryBlueprintLayoutAndShareTheOuterLayoutAndSummary(): void
    {
        $html = (string)$this->helper()
            ->form(new ArrayContext([
                'defaults' => ['title' => 'Example', 'first_name' => '<Ada>', 'author' => ['first_name' => 'Grace']],
                'errors' => ['title' => ['invalid' => 'Fix title'], 'first_name' => ['invalid' => 'Fix name']],
            ]))
            ->blueprint('composed-post')
            ->blueprint('composed-author')
            ->template('forms/editor')
            ->errorSummary()
            ->submit('Save all', config: ButtonConfig::builder()->name('intent')->value('save'));
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//form)'));
        static::assertSame(1.0, $xpath->evaluate('count(//article[@data-form-layout])'));
        static::assertSame(1.0, $xpath->evaluate('count(//div[@data-form-body]/article[@data-post])'));
        static::assertSame(1.0, $xpath->evaluate('count(//div[@data-form-body]/section[@data-author])'));
        static::assertSame(1.0, $xpath->evaluate('count(//article[@data-post]/section[@data-author])'));
        static::assertSame(1.0, $xpath->evaluate('count(//button[@name="intent"][@value="save"])'));
        static::assertSame('<Ada>', $xpath->evaluate('string(//input[@name="first_name"]/@value)'));
        static::assertSame('Grace', $xpath->evaluate('string(//input[@name="author[first_name]"]/@value)'));
        static::assertSame(2.0, $xpath->evaluate('count(//article[@data-form-layout]/section//li)'));
        static::assertStringContainsString('&lt;Ada&gt;', $html);
        static::assertLessThan(strpos($html, 'data-form-body'), strpos($html, 'Fix title'));
        static::assertLessThan(strpos($html, 'name="first_name"'), strpos($html, 'name="title"'));
        static::assertSame(['post', 'author', 'author'], array_column($this->probe->calls, 'name'));
        static::assertSame(['', 'author', ''], array_map(
            static fn(array $call): string => $call['context']->path,
            $this->probe->calls,
        ));
    }

    public function testManualControlsResolveDefaultsFromEveryBlueprintWithoutRenderingOrRecompiling(): void
    {
        $form = $this->helper()
            ->form()
            ->blueprint(PostForm::class)
            ->blueprint(AuthorForm::class)
            ->bind();
        static::assertSame(
            'Author name from parent',
            $form->field('author.first_name')->resolve()->state->config->label,
        );
        static::assertSame('First <name>', $form->field('first_name')->resolve()->state->config->label);
        static::assertSame('email', $form->field('email')->resolve()->state->type);
        static::assertSame('email', $form->field('author.email')->resolve()->state->type);

        $html = $form->open() . $form->blueprint()->render() . $form->close();
        static::assertSame(
            0.0,
            $this->xpath($html)->evaluate('count(//input[@name="internal" or @name="author[internal]"])'),
        );
        static::assertSame(['post', 'author', 'author'], array_column($this->probe->calls, 'name'));
    }

    public function testBlueprintsRenderInSelectionOrderAndBindingsCaptureIndependentSelections(): void
    {
        $builder = $this->helper()->form()->blueprint(EmailForm::class);
        $first = $builder->bind();
        $builder->blueprint(TitleForm::class);
        $html = $builder->render();
        static::assertLessThan(strpos($html, 'name="title"'), strpos($html, 'name="email"'));
        static::assertStringNotContainsString('name="title"', $first->render());
        $builder->blueprint(null)->blueprint(TitleForm::class);
        $reset = $builder->render();
        static::assertStringContainsString('name="title"', $reset);
        static::assertStringNotContainsString('name="email"', $reset);
    }

    /** @return array<string, array{string, string}> Collisions across aliases, root fields, and repeated embeds. */
    public static function duplicateSelections(): array
    {
        return [
            'alias and class' => ['composed-post', PostForm::class],
            'overlapping field' => [TitleForm::class, PostForm::class],
            'repeated collection' => [CommentsForm::class, CommentsForm::class],
        ];
    }

    /**
     * @param string $first Initial blueprint.
     * @param string $second Conflicting blueprint.
     */
    #[DataProvider('duplicateSelections')]
    public function testDuplicateDeclarationPathsFailDuringBindingWithoutEchoing(string $first, string $second): void
    {
        $this->expectOutputString('');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate field paths or embed paths');
        $this->helper()->form()->blueprint($first)->blueprint($second)->render();
    }

    public function testUnknownLaterSelectionsFailBeforeAnyFormMarkupIsEmitted(): void
    {
        $this->expectOutputString('');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown form blueprint "missing-blueprint"');
        $this->helper()->form()->blueprint(TitleForm::class)->blueprint('missing-blueprint')->render();
    }

    public function testSelectedBlueprintsCanBeMountedTogetherWithoutChangingTheirRootDefaults(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext([
                'defaults' => ['email' => 'root@example.test', 'copy' => ['email' => 'copy@example.test']],
            ]))
            ->blueprint(TitleForm::class)
            ->blueprint(EmailForm::class)
            ->bind();
        $html = $form->open();
        $html .= $form->blueprint(at: 'copy')->render();
        $html .= $form->blueprint()->render();
        $html .= $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="copy[title]"])'));
        static::assertSame('copy@example.test', $xpath->evaluate('string(//input[@name="copy[email]"]/@value)'));
        static::assertSame('root@example.test', $xpath->evaluate('string(//input[@name="email"]/@value)'));
        static::assertSame('Default email', $form->field('email')->resolve()->state->config->label);
    }

    public function testComposedFieldsAndRepeatedRowsShareCsrfMethodOverridesAndTamperProtection(): void
    {
        $csrf = bin2hex(random_bytes(16));
        $helper = $this->helper(attributes: ['csrfToken' => $csrf, 'formTokenData' => []]);
        $html = $helper->form(new ArrayContext([
            'defaults' => [
                'id' => 42,
                'title' => 'Example',
                'email' => 'ada@example.test',
                'comments' => [7 => ['body' => 'A comment']],
            ],
        ]))
            ->blueprint(TitleIdForm::class)
            ->blueprint(EmailForm::class)
            ->blueprint(CommentsForm::class)
            ->method('patch')
            ->url('/articles/save')
            ->submit('Save all', config: ButtonConfig::builder()->name('intent')->value('save'))
            ->render();
        $xpath = $this->xpath($html);
        static::assertSame('PATCH', $xpath->evaluate('string(//input[@name="_method"]/@value)'));
        static::assertSame($csrf, $xpath->evaluate('string(//input[@name="_csrfToken"]/@value)'));
        static::assertSame('A comment', $xpath->evaluate('string(//input[@name="comments[7][body]"]/@value)'));
        $payload = [
            'id' => '42',
            'title' => 'Changed',
            'email' => 'ada@example.test',
            'comments' => [7 => ['body' => 'Changed comment']],
            'intent' => 'save',
            '_Token' => [
                'fields' => $xpath->evaluate('string(//input[@name="_Token[fields]"]/@value)'),
                'unlocked' => $xpath->evaluate('string(//input[@name="_Token[unlocked]"]/@value)'),
                'debug' => $xpath->evaluate('string(//input[@name="_Token[debug]"]/@value)'),
            ],
        ];
        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate($payload, '/articles/save', $session));
        static::assertFalse(new FormProtector()->validate(
            array_replace($payload, ['id' => '99']),
            '/articles/save',
            $session,
        ));
        static::assertFalse(new FormProtector()->validate(
            $payload + ['injected' => 'yes'],
            '/articles/save',
            $session,
        ));
        unset($payload['comments']);
        static::assertFalse(new FormProtector()->validate($payload, '/articles/save', $session));
    }

    public function testASecondLayoutFailureRollsBackTheEntireCompositionsInputRegistrations(): void
    {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form()->blueprint(AuthorForm::class)->blueprint(FailingPostForm::class)->bind();
        $opening = $form->open();
        $this->expectOutputString('');
        try {
            $form->blueprint()->render();
            static::fail('Expected the second layout to fail.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Definition layout failed.', $runtimeException->getMessage());
        }

        $html =
            $opening
            . $form->blueprint(AuthorForm::class)->render()
            . $form->control('title')->render()
            . $form->close();
        $xpath = $this->xpath($html);
        static::assertSame(1.0, $xpath->evaluate('count(//input[@name="first_name"])'));
        $payload = [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
            'id' => '7',
            'title' => 'Retry',
            '_Token' => [
                'fields' => $xpath->evaluate('string(//input[@name="_Token[fields]"]/@value)'),
                'unlocked' => $xpath->evaluate('string(//input[@name="_Token[unlocked]"]/@value)'),
                'debug' => $xpath->evaluate('string(//input[@name="_Token[debug]"]/@value)'),
            ],
        ];
        static::assertTrue(new FormProtector()->validate(
            $payload,
            '/articles/save',
            $helper->getView()->getRequest()->getSession()->id(),
        ));
    }
}
