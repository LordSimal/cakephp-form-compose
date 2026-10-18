<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Builder\ConfigurationBuilder;
use FormCompose\Builder\PresentationBuilder;
use InvalidArgumentException;

/**
 * Stores immutable, presence-aware configuration behind typed domain properties.
 * Builders use declared with-methods; no public option-array import is provided.
 */
abstract class Configuration
{
    /** @param array<string, mixed> $values Explicitly supplied typed settings. */
    protected function __construct(protected readonly array $values = [])
    {
    }

    /** Read presentation without evaluating field values or producing markup. */
    public PresentationConfig $presentation {
        get {
            /** @var mixed $value */
            $value = $this->values['presentation'] ?? new PresentationConfig();
            assert($value instanceof PresentationConfig, 'Presentation changes retain their typed configuration.');

            return $value;
        }
    }

    /** Return independent configuration with the supplied presentation. */
    public function withPresentation(PresentationConfig|PresentationBuilder $presentation): static
    {
        return $this->changed('presentation', PresentationConfig::capture($presentation));
    }

    /** Overlay only settings supplied by the same configuration domain. */
    public function merge(self|ConfigurationBuilder $overrides): static
    {
        if ($overrides instanceof ConfigurationBuilder) {
            $overrides = $overrides->config();
        }

        if ($overrides::class !== static::class) {
            throw new InvalidArgumentException('Configuration domains must match.');
        }

        $values = array_replace($this->values, $overrides->values);
        if (array_key_exists('presentation', $overrides->values)) {
            $values['presentation'] = $this->presentation->merge($overrides->presentation);
        }

        return clone($this, ['values' => $values]);
    }

    /** Return a new configuration; callers cannot mutate a previously captured snapshot. */
    protected function changed(string $name, mixed $value): static
    {
        return clone($this, ['values' => array_replace($this->values, [$name => $value])]);
    }
}
