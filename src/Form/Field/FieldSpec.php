<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Configuration\FieldConfig;

/** Reusable authoring data pairing a relative field path with immutable configuration. */
final readonly class FieldSpec
{
    public FieldConfig $config;

    /** Validate a declared path and retain its immutable reusable configuration. */
    public function __construct(public string $path, ?FieldConfig $config = null)
    {
        new FieldDefinition($path);
        $this->config = $config ?? new FieldConfig();
    }
}
