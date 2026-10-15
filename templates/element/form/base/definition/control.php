<?php
declare(strict_types=1);

use FormCompose\EmbeddedDefinition;

/**
 * @var \FormCompose\DefinitionView $form
 * @var \FormCompose\FormDefinition $definition
 */
foreach ($definition->entries as $entry) {
    echo $entry instanceof EmbeddedDefinition
        ? $form->embedded($entry->path)
        : $form->control($entry->path);
}
