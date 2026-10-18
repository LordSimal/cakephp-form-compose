<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Builder\FieldConfigBuilder;
use FormCompose\Configuration\FieldConfig;

/**
 * Adds application-owned help to runtime fields through the compiler's builder.
 */
class CustomBlueprintBuilder extends BlueprintBuilder
{
    /**
     * Preserve caller options while supplying an application default.
     *
     */
    public function add(string $path, FieldConfig|FieldConfigBuilder|null $config = null): FieldConfigBuilder
    {
        $defaults = new FieldConfig(help: 'Runtime <field>');

        return parent::add($path, $defaults->merge(FieldConfig::capture($config)));
    }
}
