<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Rendering\Attributes;

/**
 * Prepared form metadata that templates can render without invoking helpers.
 */
final readonly class FormState
{
    /**
     * Describe the browser attributes, effective method, signed action, and initial hidden inputs.
     *
     * @param list<\FormCompose\Rendering\Attributes> $hidden Initial method and CSRF fields.
     * @param array<string, mixed> $presentation Template and variant configuration.
     */
    public function __construct(
        public Attributes $attributes,
        public string $method,
        public string $action,
        public array $hidden,
        public array $presentation = [],
    ) {
    }

    /**
     * Determine whether the form can submit file inputs.
     */
    public function multipart(): bool
    {
        return ($this->attributes->values['enctype'] ?? null) === 'multipart/form-data';
    }
}
