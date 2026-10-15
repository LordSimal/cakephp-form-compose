<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Html;

/**
 * Prepared option or choice group, with independent input, label, and wrapper attributes.
 */
final readonly class Choice
{
    /**
     * Describe a choice without HTML strings or a legacy widget dependency.
     *
     * @param list<\FormCompose\Field\Choice> $children Nested choices.
     * @param array<string, \FormCompose\Rendering\Attributes> $presentation Label and container attributes.
     */
    public function __construct(
        public string $kind,
        public string|Html $label,
        public Attributes $attributes,
        public array $children = [],
        public array $presentation = [],
    ) {
    }
}
