<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\DefinitionView;

/**
 * Adds application layout behavior while keeping scoped field resolution intact.
 */
class CustomDefinitionView extends DefinitionView
{
    /**
     * Annotate controls through ordinary field options.
     *
     * @param array<string, mixed> $options Per-call overrides.
     */
    public function control(string $field, array $options = []): string
    {
        return parent::control($field, array_replace($options, ['label' => 'Application layout field']));
    }
}
