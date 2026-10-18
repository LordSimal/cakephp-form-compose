<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Theme;

/** Authors a theme's ordered template layers and native attribute defaults. */
class ThemeBuilder
{
    /** @var list<string> Highest-priority layer first. */
    protected array $layers = [];

    /** @var array<string, array<string, mixed>> Attributes keyed by part or type.part. */
    protected array $defaults = [];

    /** Add a CakePHP element prefix in lookup order. */
    public function layer(string $prefix): static
    {
        $this->layers[] = $prefix;

        return $this;
    }

    /** @param array<string, mixed> $attributes Attribute defaults for a part and optional field type. */
    public function defaults(string $part, array $attributes, ?string $type = null): static
    {
        $key = $type === null ? $part : $type . '.' . $part;
        $this->defaults[$key] = new Attributes($this->defaults[$key] ?? [])->merge($attributes)->values;

        return $this;
    }

    /** Capture an immutable theme without retaining mutable authoring state. */
    public function build(): Theme
    {
        return new Theme($this->layers, $this->defaults);
    }
}
