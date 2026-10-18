<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\View\Form\ArrayContext;
use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\FieldConfig;
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
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]))
            ->bind();
        $field = $form->field('email')
            ->help('Work email')
            ->partAttributes('help', ['id' => 'custom-help'])
            ->partAttributes('errors', ['id' => 'custom-errors'])
            ->resolve();
        $form->open();
        $html = $field->control() . $form->close();
        static::assertStringContainsString('aria-describedby="custom-help custom-errors"', $html);
        static::assertStringContainsString('id="custom-help"', $html);
        static::assertStringContainsString('id="custom-errors"', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
    }

    public function testRemovedFeedbackIdsDoNotLeaveDanglingReferences(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]))
            ->bind();
        $form->open();

        $html = $form->control('email')
            ->help('Work email')
            ->partAttributes('help', ['id' => null])
            ->partAttributes('errors', ['id' => false])
            ->render();
        static::assertStringNotContainsString('aria-describedby', $html);
        static::assertStringContainsString('aria-invalid="true"', $html);
        $form->close();
    }

    #[DataProvider('feedbackParts')]
    public function testRenderTimeFeedbackIdsAreRejected(string $part): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['errors' => ['email' => ['invalid' => 'Invalid']]]))
            ->bind();
        $field = $form->field('email')->help('Work email')->resolve();
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

    #[DataProvider('relatedIdentities')]
    public function testRelatedIdsCannotCollideWithOtherControls(string $part, FieldConfig $options): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->control('field')->apply($options)->render();
        $this->expectException(LogicException::class);
        $form->control('field-' . $part)->id('form-compose-1-field-' . $part)
            ->render();
    }

    /**
     * @return array<string, array{string, \FormCompose\Configuration\FieldConfig}>
     */
    public static function relatedIdentities(): array
    {
        return [
            'help' => ['help', FieldConfig::builder()->help('Hint')],
            'radio' => [
                'a',
                FieldConfig::builder()->type('radio')->choices(['a' => 'A']),
            ],
            'checkbox group' => [
                'a',
                FieldConfig::builder()->type('multicheckbox')->choices(['a' => 'A']),
            ],
            'select option' => [
                'a',
                FieldConfig::builder()->type('select')->choices(['a' => 'A']),
            ],
            'container' => [
                'container',
                FieldConfig::builder()
                    ->partAttributes('container', ['id' => 'form-compose-1-field-container']),
            ],
            'label' => [
                'label',
                FieldConfig::builder()->partAttributes('label', ['id' => 'form-compose-1-field-label']),
            ],
        ];
    }

    public function testLateIdentityFailureRollsBackTheWholeControl(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $form->control('email-help')
            ->id('form-compose-1-email-help')
            ->render();
        try {
            $form->control('email')->help('Colliding help')->render();
            static::fail('Expected a feedback identity collision.');
        } catch (LogicException $logicException) {
            static::assertStringContainsString('already been rendered', $logicException->getMessage());
        }

        static::assertStringContainsString('name="email"', $form->control('email')->render());
        $form->close();
    }

    public function testInspectingFeedbackAttributesReservesNothing(): void
    {
        $form = $this->helper()->form()->bind();
        $form->field('email')->help('Hint')->resolve()->attributes('help');
        $form->open();
        static::assertStringContainsString('name="email-help"', $form->control('email-help')->render());
        $form->close();
    }

    public function testChoiceIdsCannotReuseTheirOwnFieldIdentity(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();
        $this->expectException(LogicException::class);
        $form->control('status')
            ->type('radio')
            ->choices([new ChoiceConfig('a', 'A', [], 'form-compose-1-status')])
            ->render();
    }
}
