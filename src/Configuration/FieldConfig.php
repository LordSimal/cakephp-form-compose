<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use Closure;
use FormCompose\Form\Field\Translated;
use FormCompose\Rendering\Html;

/**
 * Immutable unbound field settings with named arguments and fluent configuration.
 * Explicit values, including null, remain distinct from missing configuration.
 */
final class FieldConfig extends Configuration
{
    use FieldIdentityConfigTrait;
    use FieldTextConfigTrait;
    use FieldValueConfigTrait;
    use FieldStateConfigTrait;
    use FieldChoicesConfigTrait;

    /** @var array<string, mixed> Application-owned custom type settings. */
    public array $extensionData {
        get {
            /** @var mixed $value */
            $value = $this->values['extensionData'] ?? [];
            assert(is_array($value), 'Typed configuration must retain its declared value type.');

            /** @var array<string, mixed> $value */

            return $value;
        }
    }

    /** @param array<string, mixed> $value Application-owned custom type settings. */
    public function extensionData(array $value): static
    {
        return $this->changed('extensionData', $value);
    }

    /** Whether an explicit field value was supplied. */
    public bool $hasValue {
        get => array_key_exists('value', $this->values);
    }

    /** Whether disabled state was explicitly configured. */
    public bool $hasDisabled {
        get => array_key_exists('disabled', $this->values);
    }

    /** Whether readonly state was explicitly configured. */
    public bool $hasReadonly {
        get => array_key_exists('readonly', $this->values);
    }

    /** Whether choices were supplied, including an explicit discovery opt-out. */
    public bool $hasChoices {
        get => array_key_exists('choices', $this->values);
    }

    /** Initialize common named settings; fluent methods return independent configurations. */
    public function __construct(
        string|Unspecified|null $type = Unspecified::Value,
        string|Html|Translated|Closure|Unspecified|false|null $label = Unspecified::Value,
        string|Html|Translated|Closure|Unspecified|false|null $help = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['type' => $type, 'label' => $label, 'help' => $help],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
