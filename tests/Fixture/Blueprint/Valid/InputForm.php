<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Supplies native choice, file and disabled configuration through class declarations.
 */
#[Field('tags', options: ['options' => ['a' => '<Alpha>', 'b' => 'Beta'], 'multiple' => true])]
#[Field('status', type: 'radio', help: 'Choose <one>', options: ['options' => ['a' => '<Active>', 'b' => 'Blocked']])]
#[Field('attachment', type: 'file', options: ['value' => '/private/path.txt'])]
#[Field('disabled', options: ['disabled' => true, 'value' => 'Unsubmitted'])]
class InputForm extends FormBlueprint
{
}
