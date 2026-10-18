<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ChoiceBuilder;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;

/** Immutable authoring metadata for an individual select, radio, or checkbox choice. */
final readonly class ChoiceConfig
{
    /** @param array<string, mixed> $attributes Per-choice integration attributes. */
    public function __construct(
        public mixed $value,
        public string|Html|Unspecified|null $label = Unspecified::Value,
        public array $attributes = [],
        public ?string $id = null,
    ) {
        new Attributes($attributes);
    }

    /** Create a mutable authoring builder for a choice value and label. */
    public static function builder(
        mixed $value,
        string|Html|Unspecified|null $label = Unspecified::Value,
    ): ChoiceBuilder {
        return new ChoiceBuilder(new self($value, $label));
    }

    /** @param array<string, mixed> $attributes Per-choice integration attributes. */
    public function withAttributes(array $attributes): self
    {
        return clone($this, ['attributes' => new Attributes($this->attributes)->merge($attributes)->values]);
    }

    /** Set an application-owned unique choice identity. */
    public function withId(?string $id): self
    {
        return clone($this, ['id' => $id]);
    }

    /** Return a choice with explicit text, including an intentionally blank label. */
    public function withLabel(string|Html|null $label): self
    {
        return clone($this, ['label' => $label]);
    }
}
