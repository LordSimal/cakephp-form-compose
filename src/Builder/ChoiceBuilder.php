<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ChoiceConfig;
use FormCompose\Rendering\Html;

/** Mutably authors choice metadata before capturing it in a field's choices. */
class ChoiceBuilder
{
    /** Start from an immutable choice snapshot. */
    public function __construct(protected ChoiceConfig $configuration)
    {
    }

    /** @param array<string, mixed> $attributes Integration attributes for this choice. */
    public function attributes(array $attributes): static
    {
        $this->configuration = $this->configuration->withAttributes($attributes);

        return $this;
    }

    /** Configure explicit choice text, including an intentionally blank label. */
    public function label(string|Html|null $label): static
    {
        $this->configuration = $this->configuration->withLabel($label);

        return $this;
    }

    /** Configure native disabled state for this choice. */
    public function disabled(bool $disabled = true): static
    {
        return $this->attributes(['disabled' => $disabled]);
    }

    /** Set a stable application-owned choice identity. */
    public function id(?string $id): static
    {
        $this->configuration = $this->configuration->withId($id);

        return $this;
    }

    /** Capture immutable choice metadata. */
    public function config(): ChoiceConfig
    {
        return $this->configuration;
    }
}
