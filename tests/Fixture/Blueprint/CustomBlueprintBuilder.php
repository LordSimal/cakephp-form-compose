<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintBuilder;
use FormCompose\Builder\FieldDeclarationBuilder;
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
    public function add(string $path, ?FieldConfig $config = null): FieldDeclarationBuilder
    {
        $defaults = new FieldConfig(help: 'Runtime <field>');

        return parent::add($path, $defaults->merge($config ?? new FieldConfig()));
    }
}
