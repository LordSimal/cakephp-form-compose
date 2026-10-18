<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\EmbedConfigBuilder;
use FormCompose\Rendering\Html;

/** Immutable embed configuration captured before binding or rendering. */
final class EmbedConfig extends Configuration
{
    /** @var list<int|string>|null Configured keys data. */
    public ?array $keys {
        get {
            /** @var mixed $value */
            $value = $this->values['keys'] ?? null;
            assert($value === null || is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var list<int|string>|null $value */

            return $value;
        }
    }

    /** @param list<int|string>|null $value Configured keys data. */
    public function withKeys(?array $value): static
    {
        return $this->changed('keys', $value);
    }

    /** The configured label setting. */
    public string|Html|null $label {
        get {
            /** @var mixed $value */
            $value = $this->values['label'] ?? null;
            assert(
                $value === null || is_string($value) || $value instanceof Html,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit label setting. */
    public function withLabel(string|Html|null $value): static
    {
        return $this->changed('label', $value);
    }

    /** The configured errors setting. */
    public bool $errors {
        get {
            /** @var mixed $value */
            $value = $this->values['errors'] ?? true;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit errors setting. */
    public function withErrors(bool $value): static
    {
        return $this->changed('errors', $value);
    }

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        array|Unspecified|null $keys = Unspecified::Value,
        string|Html|Unspecified|null $label = Unspecified::Value,
        bool|Unspecified $errors = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['keys' => $keys, 'label' => $label, 'errors' => $errors],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Create a configuration-only builder for complete programmatic reuse. */
    public static function builder(self|EmbedConfigBuilder|null $config = null): EmbedConfigBuilder
    {
        return new EmbedConfigBuilder(self::capture($config));
    }

    /** Capture inline builders immediately; immutable configurations are already safe to reuse. */
    public static function capture(self|EmbedConfigBuilder|null $config): self
    {
        return $config instanceof EmbedConfigBuilder ? $config->config() : $config ?? new self();
    }
}
