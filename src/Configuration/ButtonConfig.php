<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ButtonConfigBuilder;

/** Immutable button configuration captured before binding or rendering. */
final class ButtonConfig extends Configuration
{
    use ButtonStateTrait;

    /** The configured type setting. */
    public string $type {
        get {
            /** @var mixed $value */
            $value = $this->values['type'] ?? 'submit';
            assert(is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit type setting. */
    public function withType(string $value): static
    {
        return $this->changed('type', $value);
    }

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        string|Unspecified $type = Unspecified::Value,
        string|Unspecified|null $name = Unspecified::Value,
        mixed $value = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['type' => $type, 'name' => $name, 'value' => $value],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Create a configuration-only builder for complete programmatic reuse. */
    public static function builder(self|ButtonConfigBuilder|null $config = null): ButtonConfigBuilder
    {
        return new ButtonConfigBuilder(self::capture($config));
    }

    /** Capture inline builders immediately; immutable configurations are already safe to reuse. */
    public static function capture(self|ButtonConfigBuilder|null $config): self
    {
        return $config instanceof ButtonConfigBuilder ? $config->config() : $config ?? new self();
    }
}
