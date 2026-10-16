<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Field\Choices;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Enum\ArticleForm;
use FormCompose\Test\Fixture\Enum\Priority;
use FormCompose\Test\Fixture\Enum\PublicationStatus;
use FormCompose\Test\Fixture\Enum\UnbackedStatus;
use FormCompose\Test\FormTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Exercises enum choices through binding, native rendering, and blueprint configuration.
 */
class EnumChoicesTest extends FormTestCase
{
    public function testEnumClassInfersASelectAndPreservesDeclarationOrderAndEmptyCase(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['data' => ['status' => PublicationStatus::InReview]]));
        $field = $form->field('status', ['options' => PublicationStatus::class, 'empty' => 'Choose status']);
        static::assertSame('select', $field->state->type);
        $form->open();
        $html = $field->control();
        $xpath = $this->xpath($html);
        static::assertSame(3.0, $xpath->evaluate('count(//option)'));
        static::assertSame('', $xpath->evaluate('string((//option)[1]/@value)'));
        static::assertSame('Unspecified', $xpath->evaluate('string((//option)[1])'));
        static::assertSame('in-review', $xpath->evaluate('string((//option)[2]/@value)'));
        static::assertSame('In Review', $xpath->evaluate('string(//option[@selected])'));
        static::assertSame('published', $xpath->evaluate('string((//option)[3]/@value)'));
        $form->close();
    }

    /**
     * @param mixed $value Enum or browser value.
     */
    #[DataProvider('selections')]
    public function testSelectionsMatchEnumsAndSubmittedScalars(string $type, mixed $value): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('priority', [
            'type' => $type,
            'options' => Priority::class,
            'multiple' => $type === 'select',
            'value' => $value,
        ]);
        $xpath = $this->xpath($html);
        $selected = $type === 'select' ? '//option[@selected]' : '//input[@checked]';
        static::assertSame(1.0, $xpath->evaluate('count(' . $selected . ')'));
        static::assertSame('0', $xpath->evaluate('string(' . $selected . '/@value)'));
        static::assertStringContainsString('Standard &lt;priority&gt;', $html);
        static::assertStringNotContainsString('<priority>', $html);
        static::assertSame(
            $type === 'radio' ? 'priority' : 'priority[]',
            $xpath->evaluate(
                'string(' . ($type === 'select' ? '//select' : $selected) . '/@name)',
            ),
        );
        $form->close();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function selections(): array
    {
        return [
            'radio enum' => ['radio', Priority::Normal],
            'radio browser value' => ['radio', '0'],
            'select enum array' => ['select', [Priority::Normal]],
            'select browser array' => ['select', ['0']],
            'checkbox enum array' => ['multicheckbox', [Priority::Normal]],
            'checkbox browser array' => ['multicheckbox', ['0']],
        ];
    }

    public function testSubsetsGroupsAndExtendedChoicesPreservePresentation(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('status', [
            'options' => [
                'Workflow' => [
                    99 => PublicationStatus::Published,
                    ['value' => PublicationStatus::InReview, 'text' => 'Review <now>', 'disabled' => true],
                    ['value' => Priority::Normal, 'data-priority' => 'normal'],
                ],
            ],
            'empty' => false,
            'value' => PublicationStatus::Published,
        ]);
        $xpath = $this->xpath($html);
        static::assertSame(3.0, $xpath->evaluate('count(//optgroup[@label="Workflow"]/option)'));
        static::assertSame('published', $xpath->evaluate('string((//option)[1]/@value)'));
        static::assertSame('published', $xpath->evaluate('string(//option[@selected]/@value)'));
        static::assertSame(1.0, $xpath->evaluate('count(//option[@value="in-review"][@disabled])'));
        static::assertSame('normal', $xpath->evaluate('string(//option[@value="0"]/@data-priority)'));
        static::assertStringContainsString('Review &lt;now&gt;', $html);
        static::assertStringContainsString('Standard &lt;priority&gt;', $html);
        $form->close();
    }

    public function testExplicitBlankAndTrustedChoiceLabelsOverrideEnumLabels(): void
    {
        $form = $this->helper()->bind();
        $form->open();

        $html = $form->control('priority', [
            'options' => [
                ['value' => Priority::Normal, 'text' => null],
                ['value' => Priority::Urgent, 'text' => new Html('<strong>Urgent</strong>')],
            ],
            'empty' => false,
        ]);
        static::assertSame('', $this->xpath($html)->evaluate('string(//option[@value="0"])'));
        static::assertStringContainsString('<strong>Urgent</strong>', $html);
        static::assertStringNotContainsString('Standard', $html);
        $form->close();
    }

    public function testRequestDataOverridesAnEnumFromTheContext(): void
    {
        $form = $this->helper(['status' => 'published'])->bind(new ArrayContext([
            'data' => ['status' => PublicationStatus::InReview],
        ]));
        $form->open();

        $html = $form->control('status', ['options' => PublicationStatus::class]);
        static::assertSame('published', $this->xpath($html)->evaluate('string(//option[@selected]/@value)'));
        $form->close();
    }

    public function testEnumIterablesAreNotConsumedByInspectionOrRegistration(): void
    {
        $consumption = new stdClass();
        $consumption->started = false;

        $choices = static function () use ($consumption): Generator {
            $consumption->started = true;
            yield PublicationStatus::Published;
            yield PublicationStatus::InReview;
        };
        $form = $this->helper(attributes: ['formTokenData' => []])->bind();
        $field = $form->field('status', ['options' => $choices(), 'empty' => false]);
        $field->attributes();
        static::assertFalse($consumption->started);
        $html = $form->open() . $field->control() . $form->close();
        static::assertSame('Published', $this->xpath($html)->evaluate('string((//option)[1])'));
        static::assertStringContainsString('_Token[fields]', $html);
    }

    public function testBlueprintsAcceptEnumClassesAndCaseListsWithLocalOverrides(): void
    {
        $form = $this->helper()->bind(definition: ArticleForm::class);
        $form->open();

        $html = $form->definition();
        static::assertSame(3.0, $this->xpath($html)->evaluate('count(//select[@name="status"]/option)'));
        static::assertStringContainsString('Standard &lt;priority&gt;', $html);
        $override = $form->control('status', [
            'id' => 'status-subset',
            'options' => [PublicationStatus::Published],
            'empty' => false,
        ]);
        static::assertSame(1.0, $this->xpath($override)->evaluate('count(//option)'));
        static::assertSame(PublicationStatus::class, $form->field('status')->state->options['options']);
        $form->close();
    }

    public function testPureEnumCasesFailWithoutRegisteringTheField(): void
    {
        $form = $this->helper()->bind();
        $form->open();
        try {
            $form->control('status', ['options' => [UnbackedStatus::Published]]);
            static::fail('Expected a pure enum case to be rejected.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            static::assertSame('Choice enum cases must be backed enums.', $invalidArgumentException->getMessage());
        }

        static::assertStringContainsString('name="status"', $form->control('status', ['options' => Priority::class]));
        $form->close();
    }

    public function testChoiceResolverRejectsNonEnumClassNames(): void
    {
        $field = $this->helper()->bind()->field('status');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choice enum classes must be backed enums.');
        new Choices(Theme::base())->resolve($field->state, $field->attributes(), stdClass::class);
    }
}
