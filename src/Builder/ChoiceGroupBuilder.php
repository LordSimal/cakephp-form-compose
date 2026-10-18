<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ChoiceGroupConfig;

/** Authors named choice groups without consuming their children until field rendering. */
class ChoiceGroupBuilder
{
    /** Start from immutable group metadata. */
    public function __construct(protected ChoiceGroupConfig $configuration)
    {
    }

    /** @param iterable<array-key, mixed> $choices Replacement group children. */
    public function choices(iterable $choices): static
    {
        $this->configuration = $this->configuration->withChoices($choices);

        return $this;
    }

    /** @param array<string, mixed> $attributes Per-group integration attributes. */
    public function attributes(array $attributes): static
    {
        $this->configuration = $this->configuration->withAttributes($attributes);

        return $this;
    }

    /** Capture the group's immutable metadata. */
    public function config(): ChoiceGroupConfig
    {
        return $this->configuration;
    }
}
