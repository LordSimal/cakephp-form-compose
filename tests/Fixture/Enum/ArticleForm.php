<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use FormCompose\Attribute\Field;
use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Configures enum choices through both public blueprint authoring paths.
 */
#[Field('status', options: ['options' => PublicationStatus::class])]
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('priority', ['options' => Priority::cases()]);
    }
}
