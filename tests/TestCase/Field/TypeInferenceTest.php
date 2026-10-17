<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use Cake\View\Form\ArrayContext;
use FormCompose\Binding\FormBinding;
use FormCompose\Test\Fixture\RangeType;
use FormCompose\Test\FormTestCase;
use FormCompose\View\Helper\FormHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $form = $helper->bind(new ArrayContext(['schema' => ['total' => ['type' => 'money']]]), [
            'types' => ['range' => new RangeType()],
        ]);
        static::assertSame('range', $form->field('total')->state->type);
        $html = $form->open() . $form->control('total') . $form->close();
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
        $first = $helper->bind($context, ['typeMap' => ['money' => 'text']]);
        $helper->setConfig('typeMap.money', 'textarea');
        $second = $helper->bind($context);
        static::assertSame('text', $first->field('total')->state->type);
        static::assertSame('textarea', $first->field('payload')->state->type);
        static::assertSame('number', $first->field('count')->state->type);
        static::assertSame('textarea', $second->field('total')->state->type);
        static::assertSame('text', new FormHelper($helper->getView())->bind($context)->field('total')->state->type);
        static::assertSame('number', $first->field('total', ['type' => 'number'])->state->type);
        static::assertStringNotContainsString('typeMap', $first->open(['typeMap' => ['money' => 'file']]));
        static::assertSame('text', $first->field('total')->state->type);
        $first->close();
        $helper->setConfig('typeMap');
        static::assertSame('text', $helper->bind($context)->field('total')->state->type);
    }

    public function testDirectBindingsSupportMappingsAndRetainNativeDefaults(): void
    {
        $view = $this->helper()->getView();
        $context = new ArrayContext([]);
        $binding = new FormBinding($context, $view->getRequest(), 'direct', ['typeMap' => ['money' => 'number']]);
        static::assertSame('number', $binding->typeMap['money']);
        static::assertSame('datetime-local', $binding->typeMap['datetime']);
        static::assertSame('checkbox', $binding->typeMap['boolean']);
    }

    public function testNameConventionsAndChoicesWinOverSchemaMappingsButExplicitTypesWinOverBoth(): void
    {
        $form = $this->helper()->bind(new ArrayContext(['schema' => ['email' => ['type' => 'string']]]), [
            'typeMap' => ['string' => 'textarea'],
        ]);
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
            static::assertSame($type, $form->field($path)->state->type);
        }

        static::assertSame('password', $form->field('password')->state->type);
        static::assertSame('textarea', $form->field('email', ['type' => 'textarea'])->state->type);
        static::assertSame('select', $form->field('email', ['options' => ['a' => 'A']])->state->type);
        static::assertSame('select', $form->field('author_id')->state->type);
        static::assertSame('select', $form->field('tags._ids')->state->type);
        $form->open();
        static::assertStringContainsString('type="email"', $form->control('email'));
        static::assertStringContainsString('type="tel"', $form->control('phone'));
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
        $this->expectException(InvalidArgumentException::class);
        $this->helper()->bind(null, ['typeMap' => $map]);
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
        $helper->bind();
    }
}
