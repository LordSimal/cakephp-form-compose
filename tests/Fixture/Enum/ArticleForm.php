<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use FormCompose\Attribute\Field;
use FormCompose\FormBlueprint;
use FormCompose\FormBuilder;
use FormCompose\FormContext;

/**
 * Configures enum choices through both public blueprint authoring paths.
 */
#[Field('status', options: ['options' => PublicationStatus::class])]
class ArticleForm extends FormBlueprint
{
    public function configure(FormBuilder $form, FormContext $context): void
    {
        $form->add('priority', ['options' => Priority::cases()]);
    }
}
