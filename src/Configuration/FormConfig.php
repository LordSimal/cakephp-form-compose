<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\FormConfigBuilder;

/** Immutable form configuration captured before binding or rendering. */
final class FormConfig extends Configuration
{
    use FormSubmissionSettingsTrait;
    use FormBindingSettingsTrait;
    use FormLayoutSettingsTrait;

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        array|string|false|Unspecified|null $url = Unspecified::Value,
        string|Unspecified|null $method = Unspecified::Value,
        string|Unspecified|null $id = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['url' => $url, 'method' => $method, 'id' => $id],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Create a configuration-only builder for complete programmatic reuse. */
    public static function builder(self|FormConfigBuilder|null $config = null): FormConfigBuilder
    {
        return new FormConfigBuilder(self::capture($config));
    }

    /** Capture inline builders immediately; immutable configurations are already safe to reuse. */
    public static function capture(self|FormConfigBuilder|null $config): self
    {
        return $config instanceof FormConfigBuilder ? $config->config() : $config ?? new self();
    }
}
