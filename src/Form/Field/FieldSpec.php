<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Builder\FieldConfigBuilder;
use FormCompose\Configuration\FieldConfig;

/** Reusable authoring data pairing a relative field path with immutable configuration. */
final readonly class FieldSpec
{
    public FieldConfig $config;

    /** Validate the path and immediately capture inline builders for independent reuse. */
    public function __construct(public string $path, FieldConfig|FieldConfigBuilder|null $config = null)
    {
        new FieldDefinition($path);
        $this->config = FieldConfig::capture($config);
    }
}
