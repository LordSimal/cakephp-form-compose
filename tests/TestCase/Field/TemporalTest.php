<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\View\Form\ArrayContext;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use FormCompose\Field\NativeValue;
use FormCompose\Test\FormTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verifies temporal value normalization through bound controls and native attributes.
 */
class TemporalTest extends FormTestCase
{
    /**
     * @param mixed $value Bound temporal value.
     * @param array<string, mixed> $options Field formatting overrides.
     */
    #[DataProvider('temporalValues')]
    public function testTemporalValuesUseNativeFormats(
        string $type,
        mixed $value,
        string $expected,
        array $options = [],
    ): void {
        $form = $this->helper()->bind();
        $form->open();

        $field = $form->field('sample', ['type' => $type, 'value' => $value] + $options);
        $html = $field->input();
        static::assertSame($expected, $this->xpath($html)->evaluate('string(//input/@value)'));
        static::assertSame($value, $field->state->value);
        $form->close();
    }

    /**
     * @return array<string, array{0: string, 1: mixed, 2: string, 3?: array<string, mixed>}>
     */
    public static function temporalValues(): array
    {
        return [
            'Cake date' => ['date', new Date('2026-10-15'), '2026-10-15'],
            'Cake time' => ['time', new Time('14:23:45'), '14:23:45'],
            'Chronos date month' => ['month', new ChronosDate('2026-10-15'), '2026-10'],
            'Chronos time' => ['time', new ChronosTime('14:23:45'), '14:23:45'],
            'ISO week year' => ['week', new DateTimeImmutable('2027-01-01'), '2026-W53'],
            'date-only ISO week' => ['week', new Date('2027-01-01'), '2026-W53'],
            'timestamp epoch' => ['date', 0, '1970-01-01'],
            'timestamp timezone' => ['time', 0, '01:00:00', ['timezone' => 'Europe/Brussels']],
            'datetime milliseconds' => [
                'datetime-local',
                new DateTimeImmutable('2026-10-15 14:23:45.123456'),
                '2026-10-15T14:23:45.123',
                ['attributes' => ['step' => '0.001']],
            ],
            'time milliseconds' => [
                'time',
                new Time('14:23:45.123456'),
                '14:23:45.123',
                ['attributes' => ['step' => 'any']],
            ],
            'time interval above one second' => [
                'time',
                new Time('00:00:01.500000'),
                '00:00:01.500',
                ['attributes' => ['step' => '1.5', 'min' => '00:00:00']],
            ],
            'datetime interval above one second' => [
                'datetime-local',
                new DateTimeImmutable('2026-10-15 00:00:02.500'),
                '2026-10-15T00:00:02.500',
                ['attributes' => ['step' => 2.5, 'min' => '2026-10-15T00:00:00']],
            ],
            'local attributes determine precision' => [
                'time',
                new DateTimeImmutable('2026-10-15 14:23:45.123456'),
                '14:23:45.123',
                ['partAttributes' => ['input' => ['step' => 0.001]]],
            ],
            'submitted wall clock' => [
                'datetime-local',
                '2026-10-15T14:23:45.123',
                '2026-10-15T14:23:45.123',
                ['timezone' => 'Europe/Brussels'],
            ],
            'invalid string is escaped, not replaced' => ['date', '<invalid>', '<invalid>'],
            'empty string' => ['date', '', ''],
        ];
    }

    public function testTimezoneConversionDoesNotMutateTheBoundDateTime(): void
    {
        $value = new DateTime('2026-10-15 23:23:45', new DateTimeZone('UTC'));
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('sample', [
            'type' => 'datetime-local',
            'value' => $value,
            'timezone' => new DateTimeZone('Europe/Brussels'),
        ]);
        static::assertSame('2026-10-16T01:23:45', $this->xpath($html)->evaluate('string(//input/@value)'));
        static::assertSame('2026-10-15T23:23:45+00:00', $value->format('c'));
        $form->close();
    }

    public function testDateTimeObjectsRetainTheirNonTemporalTextRepresentation(): void
    {
        $form = $this->helper()->bind();
        $html =
            $form->open()
            . $form->control('sample', [
                'type' => 'text',
                'value' => new DateTimeImmutable('2026-10-15 14:23:45'),
            ])
            . $form->close();
        static::assertSame(
            '2026-10-15T14:23:45',
            $this->xpath($html)->evaluate('string(//input[@name="sample"]/@value)'),
        );
    }

    public function testLocalInputStepOverridesDetermineFractionalPrecision(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $field = $form->field('sample', [
            'type' => 'time',
            'value' => new Time('00:00:02.500000'),
            'attributes' => ['step' => 1],
        ]);
        $html = $field->input(['step' => '2.5']);
        static::assertSame('00:00:02.500', $this->xpath($html)->evaluate('string(//input/@value)'));
        $form->close();
    }

    #[DataProvider('schemaSteps')]
    public function testTemporalSchemaControlsSupplyValidStepDefaults(string $schemaType, string $step): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'schema' => ['sample' => ['type' => $schemaType]],
            'data' => ['sample' => new DateTimeImmutable('2026-10-15 14:23:45.123456')],
        ]));
        $field = $form->field('sample');
        static::assertSame($step, $field->attributes()->values['step']);
        static::assertSame(
            $step === '1' ? '14:23:45' : '2026-10-15T14:23:45.123',
            NativeValue::input($field->state),
        );
        $form->open();
        $html = $field->input(['step' => null]);
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//input/@step)'));
        $form->close();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function schemaSteps(): array
    {
        return [
            'time' => ['time', '1'],
            'fractional datetime' => ['datetimefractional', '0.001'],
            'fractional timestamp' => ['timestampfractional', '0.001'],
            'timestamp timezone' => ['timestamptimezone', '0.001'],
        ];
    }

    /**
     * @param mixed $value Invalid temporal value.
     * @param mixed $timezone Invalid timezone configuration.
     */
    #[DataProvider('invalidValues')]
    public function testInvalidTemporalConfigurationDoesNotRegisterInputs(
        string $type,
        mixed $value,
        mixed $timezone,
    ): void {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('sample', ['type' => $type, 'value' => $value, 'timezone' => $timezone]);
            static::fail('Expected invalid temporal configuration.');
        } catch (InvalidArgumentException) {
            static::assertStringContainsString('type="date"', $form->control('sample', [
                'type' => 'date',
                'value' => new Date('2026-10-15'),
            ]));
        }

        $form->close();
    }

    /**
     * @return array<string, array{string, mixed, mixed}>
     */
    public static function invalidValues(): array
    {
        return [
            'timezone type' => ['date', new DateTimeImmutable(), false],
            'timezone name' => ['date', new DateTimeImmutable(), 'Invalid/Timezone'],
            'date timezone' => ['date', new Date('2026-10-15'), 'UTC'],
            'time timezone' => ['time', new Time('14:23:45'), 'UTC'],
            'date without time' => ['datetime-local', new Date('2026-10-15'), null],
            'time without date' => ['date', new Time('14:23:45'), null],
            'composite value' => ['date', ['2026', '10', '15'], null],
        ];
    }
}
