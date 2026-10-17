<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Translation;

use FormCompose\Attribute\Field;
use FormCompose\Blueprint\FormBlueprint;
use function Cake\I18n\__d;

/**
 * Defers translated attribute text until a field is resolved in the current locale.
 */
#[Field(
    'title',
    label: static function (): string {
        return __d('form-compose-tests', 'Article title');
    },
    help: static function (): string {
        return __d('form-compose-tests', 'Shown publicly');
    },
)]
class ArticleForm extends FormBlueprint
{
}
