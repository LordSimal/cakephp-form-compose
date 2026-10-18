<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Rendering\RendererInterface;
use FormCompose\Rendering\Theme;

/** Binding settings for form configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FormBindingSettingsTrait
{
    /** @var list<string> Configured valueSources data. */
    public array $valueSources {
        get {
            /** @var mixed $value */
            $value = $this->values['valueSources'] ?? ['data', 'context'];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var list<string> $value */

            return $value;
        }
    }

    /** @param list<string> $value Configured valueSources data. */
    public function valueSources(array $value): static
    {
        return $this->changed('valueSources', $value);
    }

    /** @var list<string> Configured passwordFields data. */
    public array $passwordFields {
        get {
            /** @var mixed $value */
            $value = $this->values['passwordFields'] ?? ['password', 'passwd'];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var list<string> $value */

            return $value;
        }
    }

    /** @param list<string> $value Configured passwordFields data. */
    public function passwordFields(array $value): static
    {
        return $this->changed('passwordFields', $value);
    }

    /** @var array<string, string> Configured typeMap data. */
    public array $typeMap {
        get {
            /** @var mixed $value */
            $value = $this->values['typeMap'] ?? [];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var array<string, string> $value */

            return $value;
        }
    }

    /** @param array<string, string> $value Configured typeMap data. */
    public function typeMap(array $value): static
    {
        return $this->changed('typeMap', $value);
    }

    /** @var array<string, \FormCompose\Form\Field\FieldTypeInterface> Configured types data. */
    public array $types {
        get {
            /** @var mixed $value */
            $value = $this->values['types'] ?? [];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var array<string, \FormCompose\Form\Field\FieldTypeInterface> $value */

            return $value;
        }
    }

    /** @param array<string, \FormCompose\Form\Field\FieldTypeInterface> $value Configured types data. */
    public function types(array $value): static
    {
        return $this->changed('types', $value);
    }

    /** @var array<string, mixed> Configured context data. */

    public array $context {
        get {
            /** @var mixed $value */
            $value = $this->values['context'] ?? [];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var array<string, mixed> $value */

            return $value;
        }
    }

    /** @param array<string, mixed> $value Configured context data. */
    public function context(array $value): static
    {
        return $this->changed('context', $value);
    }

    /** The configured blueprint setting. */
    public ?string $blueprint {
        get {
            /** @var mixed $value */
            $value = $this->values['blueprint'] ?? null;
            assert($value === null || is_string($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit blueprint setting. */
    public function blueprint(?string $value): static
    {
        return $this->changed('blueprint', $value);
    }

    /** The configured theme setting. */
    public ?Theme $theme {
        get {
            /** @var mixed $value */
            $value = $this->values['theme'] ?? null;
            assert(
                $value === null || $value instanceof Theme,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit theme setting. */
    public function theme(?Theme $value): static
    {
        return $this->changed('theme', $value);
    }

    /** The configured renderer setting. */
    public ?RendererInterface $renderer {
        get {
            /** @var mixed $value */
            $value = $this->values['renderer'] ?? null;
            assert(
                $value === null || $value instanceof RendererInterface,
                'Typed configuration must retain its declared value type.',
            );

            return $value;
        }
    }

    /** Return configuration with an explicit renderer setting. */
    public function renderer(?RendererInterface $value): static
    {
        return $this->changed('renderer', $value);
    }
}
