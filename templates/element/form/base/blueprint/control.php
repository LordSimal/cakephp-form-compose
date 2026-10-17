<?php
declare(strict_types=1);

use FormCompose\Blueprint\EmbeddedDefinition;
use FormCompose\Blueprint\RepeatedDefinition;

/**
 * @var \FormCompose\Blueprint\BlueprintView $form
 * @var \FormCompose\Blueprint\FormDefinition $definition
 */
foreach ($definition->entries as $entry) {
    echo match (true) {
        $entry instanceof RepeatedDefinition => $form->embeddedMany($entry->path),
        $entry instanceof EmbeddedDefinition => $form->embedded($entry->path),
        default => $form->control($entry->path),
    };
}
