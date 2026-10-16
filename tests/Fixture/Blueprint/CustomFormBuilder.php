<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\FormBuilder;

/**
 * Adds application-owned help to runtime fields through the compiler's builder.
 */
class CustomFormBuilder extends FormBuilder
{
    /**
     * Preserve caller options while supplying an application default.
     *
     * @param array<string, mixed> $options Field configuration.
     */
    public function add(string $path, array $options = []): static
    {
        return parent::add($path, $options + ['help' => 'Runtime <field>']);
    }
}
