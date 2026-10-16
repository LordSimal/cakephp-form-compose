<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Authoring;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;

/**
 * Exercises blueprint authoring through real form binding and rendering.
 */
#[Field('email', type: 'text', label: 'Session email', help: 'Session help')]
#[Field('unrendered')]
class SessionEmailForm extends FormBlueprint
{
}
