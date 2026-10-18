<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\SummaryConfigBuilder;
use FormCompose\Rendering\Html;

/** Immutable summary configuration captured before binding or rendering. */
final class SummaryConfig extends Configuration
{
    /** The configured title setting. */
    public string|Html|null $title {
        get {
            /** @var mixed $value */
            $value = $this->values['title'] ?? null;
            assert(
                $value === null || is_string($value) || $value instanceof Html,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit title setting. */
    public function withTitle(string|Html|null $value): static
    {
        return $this->changed('title', $value);
    }

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        string|Html|Unspecified|null $title = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['title' => $title],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Create a configuration-only builder for complete programmatic reuse. */
    public static function builder(self|SummaryConfigBuilder|null $config = null): SummaryConfigBuilder
    {
        return new SummaryConfigBuilder(self::capture($config));
    }

    /** Capture inline builders immediately; immutable configurations are already safe to reuse. */
    public static function capture(self|SummaryConfigBuilder|null $config): self
    {
        return $config instanceof SummaryConfigBuilder ? $config->config() : $config ?? new self();
    }
}
