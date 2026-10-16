<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

use FormCompose\Attribute\Field;
use FormCompose\Attribute\Form;
use FormCompose\FormBlueprint;

/**
 * Supplies static configuration and explicit false/null/zero values for binding tests.
 */
#[Form('profile')]
#[Field('name', label: 'Full <name>')]
#[Field(
    'email',
    type: 'email',
    label: 'Email',
    help: 'Use <work> email.',
    options: ['attributes' => ['class' => 'original']],
)]
#[Field('comments.0.body', options: ['value' => null])]
#[Field('active', type: 'checkbox', label: false, options: ['value' => false])]
#[Field('count', options: ['value' => 0])]
class ProfileForm extends FormBlueprint
{
}
