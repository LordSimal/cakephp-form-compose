<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Blueprint\BlueprintView;
use FormCompose\Builder\ControlBuilder;
use FormCompose\Builder\FieldConfigBuilder;
use FormCompose\Configuration\FieldConfig;

/**
 * Adds application layout behavior while keeping scoped field resolution intact.
 */
class CustomBlueprintView extends BlueprintView
{
    /**
     * Annotate controls through typed field configuration.
     *
     */
    public function control(string $field, FieldConfig|FieldConfigBuilder|null $config = null): ControlBuilder
    {
        return parent::control($field, FieldConfig::capture($config)->withLabel('Application layout field'));
    }
}
