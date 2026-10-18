<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

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
    public function title(string|Html|null $value): static
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

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
