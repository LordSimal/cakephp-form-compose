<?php
declare(strict_types=1);

use FormCompose\EmbeddedDefinition;
use FormCompose\RepeatedDefinition;

/**
 * @var \FormCompose\DefinitionView $form
 * @var \FormCompose\FormDefinition $definition
 */
foreach ($definition->entries as $entry):
    echo match (true) {
        $entry instanceof RepeatedDefinition => $form->embeddedMany($entry->path),
        $entry instanceof EmbeddedDefinition => $form->embedded($entry->path),
        default => $form->control($entry->path),
    };
endforeach;
