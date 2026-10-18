<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Blueprint\FormBlueprint;
use FormCompose\Blueprint\FormContext;

/**
 * Configures enum choices through both public blueprint authoring paths.
 */
class ArticleForm extends FormBlueprint
{
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
        $form->add('status')->choices(PublicationStatus::class);
        $form->add('priority')->choices(Priority::cases());
    }
}
