<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use FormCompose\Field\FieldDefinition;
use FormCompose\Rendering\Html;

/**
 * Declares one ordered field on a form blueprint, independently of bound values.
 *
 * Repeat on the class for additional fields. options accepts the same options as
 * FieldDefinition; explicitly supplied type, label, and help override those keys.
 * Use options for explicit nulls and configuration outside the common shorthand.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Field
{
    /**
     * Store reusable configuration using the existing field definition contract.
     *
     * @param array<string, mixed> $options Additional field options.
     */
    public function __construct(
        public string $path,
        public ?string $type = null,
        public string|Html|false|null $label = null,
        public string|Html|null $help = null,
        public array $options = [],
    ) {
    }

    /**
     * Convert the declaration to the same configuration used by ordinary PHP builders.
     */
    public function definition(): FieldDefinition
    {
        $options = $this->options;
        foreach (['type' => $this->type, 'label' => $this->label, 'help' => $this->help] as $key => $value) {
            if ($value === null) {
                continue;
            }
            $options[$key] = $value;
        }

        return new FieldDefinition($this->path, $options);
    }
}
