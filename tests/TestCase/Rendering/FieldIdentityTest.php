<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies feedback associations and all native identities through public field rendering.
 */
class FieldIdentityTest extends FormTestCase
{
    public function testConfiguredFeedbackIdsDriveInputReferences(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]));
        $field = $form->field('email', [
            'help' => 'Work email',
            'partAttributes' => ['help' => ['id' => 'custom-help'], 'errors' => ['id' => 'custom-errors']],
        ]);
        $form->open();
        $html = $field->control() . $form->close();
        static::assertStringContainsString('aria-describedby="custom-help custom-errors"', $html);
        static::assertStringContainsString('id="custom-help"', $html);
        static::assertStringContainsString('id="custom-errors"', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
    }

    public function testRemovedFeedbackIdsDoNotLeaveDanglingReferences(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]));
        $form->open();

        $html = $form->control('email', [
            'help' => 'Work email',
            'partAttributes' => ['help' => ['id' => null], 'errors' => ['id' => false]],
        ]);
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        $form->close();
    }

    #[DataProvider('feedbackParts')]
    public function testRenderTimeFeedbackIdsAreRejected(string $part): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]));
        $field = $form->field('email', ['help' => 'Work email']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configure help and error IDs using field partAttributes.');
        $field->attributes($part, ['id' => 'changed-after-resolution']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function feedbackParts(): array
    {
        return ['help' => ['help'], 'errors' => ['errors']];
    }

    /**
     * @param array<string, mixed> $options Field configuration.
     */
    #[DataProvider('relatedIdentities')]
    public function testRelatedIdsCannotCollideWithOtherControls(string $part, array $options): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->control('field', $options);
        $this->expectException(LogicException::class);
        $form->control('field-' . $part, ['id' => 'form-compose-1-field-' . $part]);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function relatedIdentities(): array
    {
        return [
            'help' => ['help', ['help' => 'Hint']],
            'radio' => ['a', ['type' => 'radio', 'options' => ['a' => 'A']]],
            'checkbox group' => ['a', ['type' => 'multicheckbox', 'options' => ['a' => 'A']]],
            'select option' => ['a', ['type' => 'select', 'options' => ['a' => 'A']]],
            'container' => [
                'container',
                ['partAttributes' => ['container' => ['id' => 'form-compose-1-field-container']]],
            ],
            'label' => ['label', ['partAttributes' => ['label' => ['id' => 'form-compose-1-field-label']]]],
        ];
    }

    public function testLateIdentityFailureRollsBackTheWholeControl(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $form->control('email-help', ['id' => 'form-compose-1-email-help']);
        try {
            $form->control('email', ['help' => 'Colliding help']);
            static::fail('Expected a feedback identity collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('name="email"', $form->control('email'));
        $form->close();
    }

    public function testInspectingFeedbackAttributesReservesNothing(): void
    {
        $form = $this->helper()->bind();
        $form->field('email', ['help' => 'Hint'])->attributes('help');
        $form->open();
        static::assertStringContainsString('name="email-help"', $form->control('email-help'));
        $form->close();
    }

    public function testChoiceIdsCannotReuseTheirOwnFieldIdentity(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $form->control('status', [
            'type' => 'radio',
            'options' => [['value' => 'a', 'text' => 'A', 'id' => 'form-compose-1-status']],
        ]);
    }
}
