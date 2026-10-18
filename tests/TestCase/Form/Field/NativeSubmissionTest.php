<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\Form\FormProtector;
use Dom\Element;
use Dom\NodeList;
use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Rendering\Theme;
use FormCompose\Test\Fixture\Enum\Priority;
use FormCompose\Test\FormTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks native choice behavior against Cake's real form-protection validator.
 */
class NativeSubmissionTest extends FormTestCase
{
    /**
     * @param array<string, mixed> $submitted Actual successful browser fields.
     * @param array<string, array<string, mixed>> $defaults Theme attributes.
     */
    #[DataProvider('selectSubmissions')]
    public function testSelectsProtectOnlySuccessfulInputs(
        FieldConfig $options,
        array $submitted,
        array $defaults = [],
    ): void {
        $helper = $this->helper(attributes: ['formTokenData' => []]);
        $form = $helper->form()
            ->url('/articles/save')
            ->theme(new Theme(defaults: $defaults))
            ->bind();
        $html =
            $form->open()
            . $form->control('author_id')
                ->apply($options->merge(FieldConfig::builder()->type('select')->config()))
                ->render()
            . $form->close();
        $tokens = [];
        /** @var NodeList<Element>|false $nodes */
        $nodes = $this->xpath($html)->query('//input[starts-with(@name, "_Token[")]');
        static::assertInstanceOf(NodeList::class, $nodes);
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $value = $node->getAttribute('value');
            static::assertNotNull($name);
            static::assertNotNull($value);
            $tokens[substr($name, 7, -1)] = $value;
        }

        $session = $helper->getView()->getRequest()->getSession()->id();
        static::assertTrue(new FormProtector()->validate(
            $submitted + ['_Token' => $tokens],
            '/articles/save',
            $session,
        ));
        static::assertFalse(new FormProtector()->validate(
            $submitted + ['unexpected' => 'x', '_Token' => $tokens],
            '/articles/save',
            $session,
        ));
    }

    /**
     * @return array<string, array{0: \FormCompose\Configuration\FieldConfig, 1: array<string, mixed>, 2?: array<string, array<string, mixed>>}>
     */
    public static function selectSubmissions(): array
    {
        return [
            'enum class' => [FieldConfig::builder()->choices(Priority::class)->config(), ['author_id' => '0']],
            'enum cases multiple' => [
                FieldConfig::builder()->choices(Priority::cases())->multiple(true)->config(),
                ['author_id' => ['0', '2']],
            ],
            'disabled enum select' => [FieldConfig::builder()->choices(Priority::class)->disabled(true)->config(), []],
            'disabled enum option' => [
                FieldConfig::builder()
                    ->choices([new ChoiceConfig(Priority::Normal, null, ['disabled' => true])])
                    ->placeholder(false)
                    ->config(),
                [],
            ],
            'empty single' => [FieldConfig::builder()->choices([])->placeholder(false)->config(), []],
            'required empty single' => [FieldConfig::builder()->choices([])->required(true)->config(), []],
            'optional blank' => [FieldConfig::builder()->choices([])->config(), ['author_id' => '']],
            'disabled options' => [
                FieldConfig::builder()
                    ->choices([new ChoiceConfig('a', 'A', ['disabled' => true])])
                    ->placeholder(false)
                    ->config(),
                [],
            ],
            'empty optgroup' => [FieldConfig::builder()->choices(['Group' => []])->placeholder(false)->config(), []],
            'disabled optgroup' => [
                FieldConfig::builder()
                    ->choices(['Group' => ['a' => 'A']])
                    ->placeholder(false)
                    ->config(),
                [],
                ['choice-group' => ['disabled' => true]],
            ],
            'enabled optgroup' => [
                FieldConfig::builder()
                    ->choices(['Group' => ['a' => 'A']])
                    ->placeholder(false)
                    ->config(),
                ['author_id' => 'a'],
            ],
            'empty multiple fallback' => [
                FieldConfig::builder()->choices([])->multiple(true)->config(),
                ['author_id' => ''],
            ],
            'empty multiple no fallback' => [
                FieldConfig::builder()->choices([])->multiple(true)->fallback(false)->config(),
                [],
            ],
            'disabled select' => [FieldConfig::builder()->choices(['a' => 'A'])->disabled(true)->config(), []],
        ];
    }

    public function testSinglePassOptionsAreConsumedOnlyDuringRendering(): void
    {
        $choices = static function (): Generator {
            yield 'a' => 'Alpha';
            yield 'b' => 'Beta';
        };
        $form = $this->helper(attributes: ['formTokenData' => []])->form()->bind();
        $field = $form->field('status')->choices($choices())->resolve();
        $field->attributes();

        $form->open();
        $html = $field->control() . $form->close();
        static::assertStringContainsString('Alpha', $html);
        static::assertStringContainsString('Beta', $html);
        static::assertStringContainsString('_Token[fields]', $html);
    }

    public function testRequiredCheckboxGroupsDoNotRequireEveryChoice(): void
    {
        $form = $this->helper()->form()->bind();
        $form->open();

        $html = $form->control('tags')
            ->type('multicheckbox')
            ->required(true)
            ->choices(['a' => 'A', 'b' => 'B'])
            ->render();
        static::assertSame(0.0, $this->xpath($html)->evaluate('count(//input[@type="checkbox"][@required])'));
        static::assertSame(2.0, $this->xpath($html)->evaluate('count(//input[@type="checkbox"])'));
        $radio = $form->control('status')
            ->type('radio')
            ->required(true)
            ->choices(['a' => 'A'])
            ->render();
        static::assertSame(1.0, $this->xpath($radio)->evaluate('count(//input[@type="radio"][@required])'));
        $form->close();
    }
}
