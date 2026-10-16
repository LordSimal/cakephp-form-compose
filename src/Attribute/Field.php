<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use Closure;
use FormCompose\Field\FieldDefinition;
use FormCompose\Rendering\Html;

/**
 * Declares one ordered field on a form blueprint, independently of bound values.
 *
 * Repeat on the class for additional fields. options accepts the same options as
 * inline controls; explicitly supplied type, label, and help override those keys.
 * Use options for explicit nulls and configuration outside the common shorthand.
 * Template arguments use the same normalization as ordinary control options.
 * Label/help closures take no arguments and run once per resolved field, after
 * blueprint and per-call options merge. For example, use a PHP 8.5 attribute
 * label: static function (): string { return __('Article title'); }.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Field
{
    /**
     * Store reusable configuration using the existing field definition contract.
     *
     * Keep existing positional arguments while exposing named presentation shorthand.
     *
     * @param string|\FormCompose\Rendering\Html|false|null|\Closure():(string|\FormCompose\Rendering\Html|false|null) $label Label or deferred text.
     * @param string|\FormCompose\Rendering\Html|false|null|\Closure():(string|\FormCompose\Rendering\Html|false|null) $help Help or deferred text.
     * @param array<string, mixed> $options Additional field options.
     * @param array<string, string>|null $templates Field part overrides.
     * @param array<string, mixed>|null $templateData Application-owned template variables.
     * @mago-expect lint:excessive-parameter-list
     */
    public function __construct(
        public string $path,
        public ?string $type = null,
        public string|Html|Closure|false|null $label = null,
        public string|Html|Closure|false|null $help = null,
        public array $options = [],
        public ?string $template = null,
        public ?array $templates = null,
        public ?array $templateData = null,
    ) {
    }

    /**
     * Convert the declaration to the compiler's field configuration.
     *
     * @internal Used during blueprint declaration collection.
     */
    public function definition(): FieldDefinition
    {
        $options = $this->options;
        foreach ([
            'type' => $this->type,
            'label' => $this->label,
            'help' => $this->help,
            'template' => $this->template,
            'templates' => $this->templates,
            'templateData' => $this->templateData,
        ] as $key => $value) {
            if ($value === null) {
                continue;
            }

            $options[$key] = $value;
        }

        return new FieldDefinition($this->path, $options);
    }
}
