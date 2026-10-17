<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Binding;

use Cake\View\Form\ArrayContext;
use FormCompose\Binding\RepeatedErrors;
use FormCompose\Blueprint\FormContext;
use FormCompose\Test\FormTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Keeps direct collection feedback independent of selected values and stale row errors.
 */
class RepeatedErrorsTest extends FormTestCase
{
    /** @param mixed $submitted Submitted collection value. */
    #[DataProvider('submittedCollections')]
    public function testNestedRowErrorsAreExcludedRegardlessOfSubmittedKeys(mixed $submitted): void
    {
        $helper = $this->helper(['comments' => $submitted]);
        $form = $helper->bind(new ArrayContext([
            'defaults' => ['comments' => [0 => ['body' => 'Loaded']]],
            'errors' => [
                'comments' => [
                    'count' => 'Add another comment',
                    0 => ['body' => ['required' => 'Old numeric row']],
                    'stale' => ['body' => ['required' => 'Old string row']],
                    'unknown' => ['author' => ['name' => ['required' => 'Unloaded nested row']]],
                ],
            ],
        ]));
        static::assertSame(['Add another comment'], RepeatedErrors::messages(
            FormContext::fromBinding($form->binding),
            'comments',
        ));
    }

    /** @return array<string, array{mixed}> */
    public static function submittedCollections(): array
    {
        return [
            'cleared' => [[]],
            'null' => [null],
            'false' => [false],
            'empty string' => [''],
            'malformed scalar' => ['invalid'],
            'replacement keys' => [['new' => ['body' => 'New']]],
        ];
    }

    public function testDirectListMessagesAreRetainedInsideAScopedContext(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => [
                'post' => [
                    'comments' => [
                        'First direct error',
                        'Second direct error',
                        'omitted' => ['body' => ['required' => 'Child error']],
                    ],
                ],
            ],
        ]));
        static::assertSame(['First direct error', 'Second direct error'], RepeatedErrors::messages(
            FormContext::fromBinding($form->binding)->at('post'),
            'comments',
        ));
    }
}
