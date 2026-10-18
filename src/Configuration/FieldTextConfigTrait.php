<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use Closure;
use FormCompose\Form\Field\Translated;
use FormCompose\Rendering\Html;

/** Typed text settings shared by immutable field configuration.
 * @require-extends \FormCompose\Configuration\Configuration
 */
trait FieldTextConfigTrait
{
    /** The configured label setting. */
    public string|Html|Translated|Closure|false|null $label {
        get {
            /** @var mixed $value */
            $value = $this->values['label'] ?? null;
            assert(
                $value === null
                    || $value === false
                    || is_string($value)
                    || $value instanceof Html
                    || $value instanceof Translated
                    || $value instanceof Closure,
                'Field text retains a supported text or deferred value.',
            );

            return $value;
        }
    }

    /** The configured help setting. */
    public string|Html|Translated|Closure|false|null $help {
        get {
            /** @var mixed $value */
            $value = $this->values['help'] ?? null;
            assert(
                $value === null
                    || $value === false
                    || is_string($value)
                    || $value instanceof Html
                    || $value instanceof Translated
                    || $value instanceof Closure,
                'Field text retains a supported text or deferred value.',
            );

            return $value;
        }
    }

    /** The configured errors setting. */
    public bool $errors {
        get {
            /** @var mixed $value */
            $value = $this->values['errors'] ?? true;
            assert(is_bool($value), 'Typed configuration must retain its declared value type.');

            return $value;
        }
    }

    /** Return configuration with an explicit label setting. */
    public function withLabel(string|Html|Translated|Closure|false|null $value): static
    {
        return $this->changed('label', $value);
    }

    /** Return configuration with an explicit help setting. */
    public function withHelp(string|Html|Translated|Closure|false|null $value): static
    {
        return $this->changed('help', $value);
    }

    /** Return configuration with an explicit errors setting. */
    public function withErrors(bool $value): static
    {
        return $this->changed('errors', $value);
    }
}
