<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ChoiceGroupBuilder;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;

/** Immutable named choice group; its iterable is consumed only by choice rendering. */
final readonly class ChoiceGroupConfig
{
    /** @var iterable<array-key, mixed> Captured array choices or an unconsumed lazy source. */
    public iterable $choices;

    /**
     * @param iterable<array-key, mixed> $choices Group children.
     * @param array<string, mixed> $attributes Group attributes.
     */
    public function __construct(public string|Html $label, iterable $choices, public array $attributes = [])
    {
        new Attributes($attributes);
        $this->choices = ChoiceList::capture($choices);
    }

    /** @param iterable<array-key, mixed> $choices Group children, consumed only when rendering. */
    public static function builder(string|Html $label, iterable $choices = []): ChoiceGroupBuilder
    {
        return new ChoiceGroupBuilder(new self($label, $choices));
    }

    /** @param iterable<array-key, mixed> $choices Replacement group children. */
    public function withChoices(iterable $choices): self
    {
        return clone($this, ['choices' => ChoiceList::capture($choices)]);
    }

    /** @param array<string, mixed> $attributes Per-group integration attributes. */
    public function withAttributes(array $attributes): self
    {
        return clone($this, ['attributes' => new Attributes($this->attributes)->merge($attributes)->values]);
    }
}
