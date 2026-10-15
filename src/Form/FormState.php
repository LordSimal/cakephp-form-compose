<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Rendering\Attributes;

/**
 * Stores prepared form metadata for lifecycle checks and form templates.
 *
 * FormConstruction creates it from a FormBinding and opening options. attributes
 * contains the browser method and action; method retains the effective HTTP verb
 * and action the path/query used for signing. hidden contains the initial method
 * and CSRF inputs; final form-protection inputs are collected during closing.
 */
final readonly class FormState
{
    /**
     * Describe the browser attributes, effective method, signed action, and initial hidden inputs.
     *
     * @param list<\FormCompose\Rendering\Attributes> $hidden Initial method and CSRF fields.
     * @param array<string, mixed> $presentation Templates, variant, and templateData configuration.
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
