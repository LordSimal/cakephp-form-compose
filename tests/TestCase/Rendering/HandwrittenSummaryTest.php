<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\Fixture\Authoring\BodyForm;
use FormCompose\Test\FormTestCase;
use Generator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies summaries use emitted identities rather than merely configured native choices.
 */
class HandwrittenSummaryTest extends FormTestCase
{
    #[DataProvider('groupedTypes')]
    public function testHandwrittenChoiceGroupsRemainUnlinked(string $type): void
    {
        $options = static function (): Generator {
            yield 'a' => 'A';
        };
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['status' => ['invalid' => 'Choose one']]]))
            ->bind();
        $field = $form->field('status')->type($type)->choices($options())->resolve();
        $form->open();

        $body =
            '<input'
            . (string)$field->attributes()
                ->replace([
                    'type' => $type === 'radio' ? 'radio' : 'checkbox',
                ])
            . '>';
        $field->register();
        $body .= $field->errors()->render();
        $summary = $form->errorSummary()->render();
        static::assertStringContainsString('Status: Choose one', $summary);
        static::assertStringNotContainsString('<a ', $summary);
        static::assertStringContainsString('id="form-compose-1-status"', $body);
        static::assertStringNotContainsString('id="form-compose-1-status-a"', $body);
        static::assertStringContainsString(
            'id="form-compose-1-status-a"',
            $form->control('other')->id('form-compose-1-status-a')->render(),
        );
        $form->close();
    }

    /** @return array<string, array{string}> */
    public static function groupedTypes(): array
    {
        return ['radio' => ['radio'], 'checkboxes' => ['multicheckbox']];
    }

    public function testHandwrittenRetryAfterFailedRenderingDoesNotReuseTheRenderedTarget(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['status' => ['invalid' => 'Choose one']]]))
            ->bind();
        $field = $form->field('status')
            ->type('radio')
            ->choices(['a' => 'A'])
            ->partAttributes('container', ['id' => 'occupied'])
            ->resolve();
        $form->open();
        $form->control('other')->id('occupied')->render();
        try {
            $field->control();
            static::fail('Expected container collision after native rendering.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered: occupied', $logicException->getMessage());
        }

        $field->register();
        $field->errors()->render();

        $summary = $form->errorSummary()->render();
        static::assertStringContainsString('Status: Choose one', $summary);
        static::assertStringNotContainsString('<a ', $summary);
        static::assertStringContainsString(
            'id="form-compose-1-status-a"',
            $form->control('spare')->id('form-compose-1-status-a')->render(),
        );
        $form->close();
    }

    public function testClearedCollectionsDoNotSummarizeStaleChildErrors(): void
    {
        $form = $this->helper(['comments' => []])
            ->form(new ArrayContext([
                'defaults' => ['comments' => [0 => ['body' => 'Loaded']]],
                'errors' => [
                    'comments' => [
                        'count' => 'Add a comment',
                        0 => ['body' => ['required' => 'Old row error']],
                    ],
                ],
            ]))
            ->bind();
        $form->open();
        $form->embedMany(path: 'comments', blueprint: BodyForm::class)->render();

        $summary = $form->errorSummary()->render();
        static::assertStringContainsString('Comments: Add a comment', $summary);
        static::assertStringNotContainsString('Old row error', $summary);
        static::assertStringNotContainsString('<a ', $summary);
        $form->close();
    }
}
