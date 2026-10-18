<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Binding\FormBinding;
use FormCompose\Configuration\FormConfig;
use FormCompose\Test\Fixture\RangeType;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

/**
 * Verifies schema mappings, name conventions, and configuration precedence at binding.
 */
class TypeInferenceTest extends FormTestCase
{
    public function testCustomSchemaMappingsSelectRegisteredTypesWithoutChangingSubmittedNames(): void
    {
        $view = $this->helper()->getView();
        $helper = $view->loadHelper('Compose', [
            'className' => 'FormCompose.Form',
            'typeMap' => ['money' => 'range'],
        ]);
        static::assertInstanceOf(FormHelper::class, $helper);
        $form = $helper->form(new ArrayContext(['schema' => ['total' => ['type' => 'money']]]))
            ->types(['range' => new RangeType()])
            ->bind();
        static::assertSame('range', $form->field('total')->resolve()->state->type);
        $html = $form->open() . $form->control('total')->render() . $form->close();
        static::assertStringContainsString('name="total[start]"', $html);
        static::assertStringContainsString('name="total[end]"', $html);
        static::assertStringNotContainsString('typeMap', $html);
    }

    public function testBindingOverridesMergeAndExistingBindingsKeepTheirSnapshot(): void
    {
        $helper = $this->helper();
        $helper->setConfig('typeMap', ['money' => 'number', 'json' => 'textarea']);

        $context = new ArrayContext([
            'schema' => [
                'total' => ['type' => 'money'],
                'payload' => ['type' => 'json'],
                'count' => ['type' => 'integer'],
            ],
        ]);
        $first = $helper->form($context)
            ->typeMap(['money' => 'text'])
            ->bind();
        $helper->setConfig('typeMap.money', 'textarea');
        $second = $helper->form($context)->bind();
        static::assertSame('text', $first->field('total')->resolve()->state->type);
        static::assertSame('textarea', $first->field('payload')->resolve()->state->type);
        static::assertSame('number', $first->field('count')->resolve()->state->type);
        static::assertSame('textarea', $second->field('total')->resolve()->state->type);
        static::assertSame(
            'text',
            new FormHelper($helper->getView())->form($context)->bind()->field('total')->resolve()->state->type,
        );
        static::assertSame(
            'number',
            $first->field('total')->type('number')->resolve()->state->type,
        );
        static::assertStringNotContainsString('typeMap', $first->open());
        static::assertSame('text', $first->field('total')->resolve()->state->type);
        $first->close();
        $helper->setConfig('typeMap');
        static::assertSame('text', $helper->form($context)->bind()->field('total')->resolve()->state->type);
    }

    public function testDirectBindingsSupportMappingsAndRetainNativeDefaults(): void
    {
        $view = $this->helper()->getView();
        $context = new ArrayContext([]);
        $binding = new FormBinding(
            $context,
            $view->getRequest(),
            'direct',
            FormConfig::builder()->typeMap(['money' => 'number']),
        );
        static::assertSame('number', $binding->typeMap['money']);
        static::assertSame('datetime-local', $binding->typeMap['datetime']);
        static::assertSame('checkbox', $binding->typeMap['boolean']);
    }

    public function testNameConventionsAndChoicesWinOverSchemaMappingsButExplicitTypesWinOverBoth(): void
    {
        $form = $this->helper()
            ->form(new ArrayContext(['schema' => ['email' => ['type' => 'string']]]))
            ->typeMap(['string' => 'textarea'])
            ->bind();
        foreach ([
            'email' => 'email',
            'account.email' => 'email',
            'phone' => 'tel',
            'account.telephone' => 'tel',
            'tel' => 'tel',
            'telephone' => 'tel',
            'email_address' => 'text',
            'cellphone' => 'text',
        ] as $path => $type) {
            static::assertSame($type, $form->field($path)->resolve()->state->type);
        }

        static::assertSame('password', $form->field('password')->resolve()->state->type);
        static::assertSame(
            'textarea',
            $form->field('email')->type('textarea')->resolve()->state->type,
        );
        static::assertSame(
            'select',
            $form->field('email')
                ->choices(['a' => 'A'])
                ->resolve()
                ->state
                ->type,
        );
        static::assertSame('select', $form->field('author_id')->resolve()->state->type);
        static::assertSame('select', $form->field('tags._ids')->resolve()->state->type);
        $form->open();
        static::assertStringContainsString('type="email"', $form->control('email')->render());
        static::assertStringContainsString('type="tel"', $form->control('phone')->render());
        $form->close();
    }

    /**
     * Supply malformed configuration before it reaches rendering.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidMaps(): array
    {
        return [
            'scalar' => ['number'],
            'null' => [null],
            'list' => [['text']],
            'empty key' => [['' => 'text']],
            'invalid alias' => [['money' => 'Currency']],
            'empty alias' => [['money' => '']],
            'nonstring alias' => [['money' => false]],
        ];
    }

    #[DataProvider('invalidMaps')]
    public function testInvalidBindingMappingsAreRejected(mixed $map): void
    {
        $this->expectException(is_array($map) ? InvalidArgumentException::class : TypeError::class);
        $builder = $this->helper()->form();
        $this->invokeBuilder($builder, 'typeMap', $map);
        $builder->bind();
    }

    /**
     * Supply invalid stored helper options; Cake treats setConfig with null as deletion.
     *
     * @return array<string, array{mixed}>
     */
    public static function invalidHelperMaps(): array
    {
        return array_diff_key(self::invalidMaps(), ['null' => true]);
    }

    #[DataProvider('invalidHelperMaps')]
    public function testInvalidHelperMappingsAreRejected(mixed $map): void
    {
        $helper = $this->helper();
        $helper->setConfig('typeMap', $map, false);
        $this->expectException(InvalidArgumentException::class);
        $helper->form()->bind();
    }
}
