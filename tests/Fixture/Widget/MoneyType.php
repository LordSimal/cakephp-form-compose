<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;
use FormCompose\Form\Field\FieldSpec;
use function Cake\I18n\__;

/**
 * Exercises the documented amount/currency widget with native child protocols.
 */
#[AsFieldType('money')]
class MoneyType extends CompositeType
{
    protected ?string $template = 'widgets/money';

    /**
     * @return list<\FormCompose\Form\Field\FieldSpec>
     */
    public function inputs(BoundField $field): array
    {
        return [
            new FieldSpec(
                'amount',
                FieldConfig::builder()
                    ->type('number')
                    ->label(__('Amount'))
                    ->attributes(['step' => '0.01', 'min' => '0'])
                    ->config(),
            ),
            new FieldSpec(
                'currency',
                FieldConfig::builder()
                    ->type('select')
                    ->label(__('Currency'))
                    ->choices(['EUR' => __('Euro'), 'USD' => __('US dollar')])
                    ->config(),
            ),
        ];
    }
}
