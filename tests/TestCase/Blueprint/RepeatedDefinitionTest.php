<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Blueprint;

use Cake\View\Form\ArrayContext;
use FormCompose\Attribute\EmbedMany;
use FormCompose\Test\Fixture\Authoring\CommentsForm;
use FormCompose\Test\Fixture\Authoring\SuppressedCommentsForm;
use FormCompose\Test\Fixture\Repeat\CommentForm;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Covers blueprint row selection, collection feedback, and atomic rendering failures.
 */
class RepeatedDefinitionTest extends FormTestCase
{
    public function testRepeatedBlueprintsCompileIndependentlyPerBinding(): void
    {
        $first = $this->helper(['comments' => [2 => ['body' => 'First']]])->bind(
            blueprint: CommentsForm::class,
        );
        $second = $this->helper(['comments' => ['new' => ['body' => 'Second']]])->bind(
            blueprint: CommentsForm::class,
        );
        static::assertTrue($first->binding->definition?->has('comments.2.body'));
        static::assertFalse($second->binding->definition?->has('comments.2.body'));
        static::assertStringContainsString('comments[new][body]', $second->render());
    }

    /** @param mixed $value Submitted collection value. */
    #[DataProvider('emptyCollections')]
    public function testClearedAndMalformedSubmissionsDoNotRestoreLoadedRows(mixed $value): void
    {
        $context = new ArrayContext(['defaults' => ['comments' => [0 => ['body' => 'Old']]]]);
        $form = $this->helper(['comments' => $value])->bind($context);
        $form->open();
        static::assertSame('', trim($form->embedMany('comments', CommentForm::class)));
        $form->close();
    }

    /** @return array<string, array{mixed}> */
    public static function emptyCollections(): array
    {
        return [
            'empty array' => [[]],
            'empty string' => [''],
            'null' => [null],
            'false' => [false],
            'scalar' => ['invalid'],
        ];
    }

    public function testExplicitKeysSelectOrderAndCanSuppressAllRows(): void
    {
        $form = $this->helper(['comments' => [0 => ['body' => 'Zero'], 2 => ['body' => 'Two']]])->bind();
        $form->open();
        static::assertSame('', trim($form->embedMany('comments', CommentForm::class, keys: [])));
        $html = $form->embedMany(
            'comments',
            CommentForm::class,
            keys: [2, 0],
            options: ['template' => 'repeat/comments', 'attributes' => ['class' => 'rows']],
        );
        static::assertLessThan(strpos($html, 'Zero'), strpos($html, 'Two'));
        static::assertStringContainsString('class="rows comments"', $html);
        $form->close();
    }

    public function testCollectionErrorsExcludeChildErrorsEvenForOmittedRows(): void
    {
        $context = new ArrayContext([
            'defaults' => ['comments' => [0 => ['body' => 'First'], 2 => ['body' => 'Second']]],
            'errors' => [
                'comments' => [
                    'count' => 'Need <more>',
                    0 => ['body' => ['invalid' => 'Child zero']],
                    2 => ['body' => ['invalid' => 'Child two']],
                ],
            ],
        ]);
        $form = $this->helper()->bind($context);
        $form->open();

        $html = $form->embedMany('comments', CommentForm::class, keys: []);
        static::assertStringContainsString('Need &lt;more&gt;', $html);
        static::assertStringNotContainsString('Child', $html);
        $form->close();
    }

    public function testSuppressedCollectionErrorsAndWholeCollectionRemoval(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['comments' => ['count' => 'Required']],
        ]), blueprint: SuppressedCommentsForm::class);
        $form->open();
        static::assertSame('', trim($form->blueprint()));
        static::assertFalse($form->binding->definition?->remove('comments')->has('comments'));
        $form->close();
    }

    public function testFailedWrapperRollsBackAllRowsAndIds(): void
    {
        $form = $this->helper(['comments' => [0 => ['body' => 'First']]])->bind();
        $form->open();
        try {
            $form->embedMany('comments', CommentForm::class, options: ['template' => 'repeat/failure']);
            static::fail('Expected failure after row rendering.');
        } catch (RuntimeException $runtimeException) {
            static::assertSame('Repeated wrapper failed.', $runtimeException->getMessage());
        }

        static::assertStringContainsString('First', $form->embedMany('comments', CommentForm::class));
        $form->close();
    }

    /** @param list<int|string>|null $keys Invalid declaration keys. */
    #[DataProvider('invalidDeclarations')]
    public function testInvalidDeclarationsFailBeforeRendering(string $path, string $definition, ?array $keys): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EmbedMany($path, $definition, $keys);
    }

    /** @return array<string, array{string, string, list<int|string>|null}> */
    public static function invalidDeclarations(): array
    {
        return [
            'path' => ['bad..path', CommentForm::class, null],
            'name' => ['comments', '', null],
            'keys' => ['comments', CommentForm::class, ['bad.key']],
            'duplicate' => ['comments', CommentForm::class, [0, '0']],
        ];
    }
}
