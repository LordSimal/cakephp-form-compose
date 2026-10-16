<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Rendering;

use Cake\AttributeResolver\AttributeResolver;
use Cake\View\Form\ArrayContext;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Rendering\Theme;
use FormCompose\Test\BlueprintTestCase;
use FormCompose\Test\Fixture\RangeType as LegacyRangeType;
use FormCompose\Test\Fixture\Summary\RangeType;
use Generator;
use InvalidArgumentException;

/**
 * Verifies actual native and composite target identities without rereading choices.
 */
class ErrorSummaryTargetsTest extends BlueprintTestCase
{
    public function testGroupedChoicesLinkToTheFirstEnabledChoiceIncludingNestedGroups(): void
    {
        $form = $this->helper()->bind(new ArrayContext([
            'errors' => ['status' => ['invalid' => 'Choose one'], 'tags' => ['invalid' => 'Choose tags']],
        ]));
        $form->open();

        $body = $form->control('status', [
            'type' => 'radio',
            'options' => [
                'Group' => [['value' => 'off', 'text' => 'Off', 'disabled' => true], ['value' => 'on', 'text' => 'On']],
            ],
        ]);
        $body .= $form->control('tags', ['type' => 'multicheckbox', 'options' => ['a' => 'A']]);
        $summary = $form->errorSummary();
        static::assertStringContainsString('href="#form-compose-1-status-on"', $summary);
        static::assertStringContainsString('href="#form-compose-1-tags-a"', $summary);
        static::assertStringNotContainsString('href="#form-compose-1-status-off"', $summary);
        static::assertStringContainsString('id="form-compose-1-status-on"', $body);
        $form->close();
    }

    public function testAllDisabledOrEmptyGroupsHaveNoTarget(): void
    {
        $form = $this->helper()->bind(
            new ArrayContext(['errors' => ['status' => ['invalid' => 'Choose one']]]),
            theme: new Theme(defaults: ['choice-group' => ['disabled' => true]]),
        );
        $form->open();
        $form->control('status', ['type' => 'radio', 'options' => ['Group' => ['a' => 'A']]]);
        static::assertStringNotContainsString('<a ', $form->errorSummary());
        $form->close();
    }

    public function testChoiceGeneratorsAreNotConsumedAgainForSummaries(): void
    {
        $options = static function (): Generator {
            yield 'a' => 'A';
        };
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['status' => ['invalid' => 'Choose one']]]));
        $form->open();
        $form->control('status', ['type' => 'radio', 'options' => $options()]);
        static::assertStringContainsString('href="#form-compose-1-status-a"', $form->errorSummary());
        $form->close();
    }

    public function testExplicitCustomTargetsAndLegacyTypesBothWork(): void
    {
        $form = $this->helper()->bind(
            new ArrayContext([
                'errors' => ['period' => ['invalid' => 'Fix period'], 'legacy' => ['invalid' => 'Fix legacy']],
            ]),
            ['types' => ['range' => new RangeType(), 'legacy' => new LegacyRangeType()]],
        );
        $form->open();
        $form->control('period', ['type' => 'range']);
        $form->control('legacy', ['type' => 'legacy']);

        $summary = $form->errorSummary();
        static::assertStringContainsString('href="#form-compose-1-period-end"', $summary);
        static::assertStringContainsString('href="#form-compose-1-legacy"', $summary);
        $form->close();
    }

    public function testCustomControlsCanOptOutOfLinking(): void
    {
        $form = $this->helper()->bind(
            new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix period']]]),
            ['types' => ['range' => new RangeType(null)]],
        );
        $form->open();
        $form->control('period', ['type' => 'range']);
        static::assertStringNotContainsString('<a ', $form->errorSummary());
        $form->close();
    }

    public function testDiscoveredLazyTypesForwardTheirCustomTargets(): void
    {
        AttributeResolver::drop('testBlueprints');
        AttributeResolver::setConfig('testBlueprints', [
            'paths' => ['tests/Fixture/Summary/RangeType.php'],
            'cache' => false,
        ]);
        $helper = $this->helper();
        $helper->setConfig('blueprintFactory', new BlueprintFactory(collection: 'testBlueprints'));

        $form = $helper->bind(new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix period']]]));
        $form->open();
        $form->control('period', ['type' => 'summary-range']);
        static::assertStringContainsString('href="#form-compose-1-period-end"', $form->errorSummary());
        $form->close();
    }

    public function testAnInvalidCustomTargetRollsBackFieldRegistration(): void
    {
        $form = $this->helper()->bind(
            new ArrayContext(['errors' => ['period' => ['invalid' => 'Fix period']]]),
            ['types' => ['range' => new RangeType('')]],
        );
        $form->open();
        try {
            $form->control('period', ['type' => 'range']);
            static::fail('Expected invalid target to fail.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertStringContainsString('nonempty IDs or null', $invalidArgumentException->getMessage());
        }

        static::assertSame('', $form->errorSummary());
        static::assertStringContainsString('name="period"', $form->control('period'));
        $form->close();
    }

    public function testEmptyAndAllDisabledNestedGroupsAreSkippedBeforeAnEnabledTarget(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['errors' => ['status' => ['invalid' => 'Choose one']]]));
        $form->open();

        $body = $form->control('status', [
            'type' => 'radio',
            'options' => [
                'Empty' => [],
                'Unavailable' => [['value' => 'off', 'text' => 'Off', 'disabled' => true]],
                'Available' => ['on' => 'On'],
            ],
        ]);
        $summary = $form->errorSummary();
        static::assertStringContainsString('href="#form-compose-1-status-on"', $summary);
        static::assertStringContainsString('id="form-compose-1-status-on"', $body);
        static::assertStringNotContainsString('href="#form-compose-1-status-off"', $summary);
        $form->close();
    }
}
