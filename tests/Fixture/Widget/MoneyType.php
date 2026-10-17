<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\CompositeType;
use function Cake\I18n\__;

/**
 * Exercises the documented amount/currency widget with native child protocols.
 */
#[AsFieldType('money')]
class MoneyType extends CompositeType
{
    protected ?string $template = 'widgets/money';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function inputs(BoundField $field): array
    {
        return [
            'amount' => [
                'type' => 'number',
                'label' => __('Amount'),
                'attributes' => ['step' => '0.01', 'min' => '0'],
            ],
            'currency' => [
                'type' => 'select',
                'label' => __('Currency'),
                'options' => ['EUR' => __('Euro'), 'USD' => __('US dollar')],
            ],
        ];
    }
}
