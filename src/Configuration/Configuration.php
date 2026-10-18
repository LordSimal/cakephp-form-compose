<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use InvalidArgumentException;

/**
 * Stores immutable, presence-aware configuration behind typed domain properties.
 * Named arguments and fluent methods create independent settings without option-array imports.
 */
abstract class Configuration
{
    use PresentationMethodsTrait;
    use ReplacementMethodsTrait;

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
    public function presentation(PresentationConfig $presentation): static
    {
        return $this->changed('presentation', $presentation);
    }

    /** Overlay only settings supplied by the same configuration domain. */
    public function merge(self $overrides): static
    {
        if ($overrides::class !== static::class) {
            throw new InvalidArgumentException('Configuration domains must match.');
        }

        $values = array_replace($this->values, $overrides->values);
        if (array_key_exists('presentation', $overrides->values)) {
            $values['presentation'] = $this->presentation->merge($overrides->presentation);
        }

        return clone($this, ['values' => $values]);
    }

    /** Read presentation while keeping field values and rendering untouched. */
    protected function currentPresentation(): PresentationConfig
    {
        return $this->presentation;
    }

    /** Return a copy with updated presentation. */
    protected function updatePresentation(PresentationConfig $presentation): static
    {
        return $this->presentation($presentation);
    }

    /** Identify the template selected by template(). */
    protected function primaryPart(): string
    {
        return 'control';
    }

    /** Return a new configuration; callers cannot mutate a previously captured snapshot. */
    protected function changed(string $name, mixed $value): static
    {
        return clone($this, ['values' => array_replace($this->values, [$name => $value])]);
    }
}
