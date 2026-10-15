<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use InvalidArgumentException;

/**
 * Names a blueprint for discovery through CakePHP's AttributeResolver.
 *
 * Apply to a FormBlueprint subclass in src/Form/; bind with its alias or class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Form
{
    /**
     * Declare an alias and optional definition layout presentation.
     *
     * @param array<string, string>|null $templates Definition part overrides; control selects the layout.
     * @param array<string, mixed>|null $templateData Application-owned layout variables.
     */
    public function __construct(
        public string $name,
        public ?string $template = null,
        public ?array $templates = null,
        public ?array $templateData = null,
    ) {
        if ($name === '' || trim($name) !== $name) {
            throw new InvalidArgumentException('Form aliases must be nonempty and have no surrounding whitespace.');
        }
    }

    /**
     * Export only explicitly supplied presentation options for inheritance.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return array_filter(
            [
                'template' => $this->template,
                'templates' => $this->templates,
                'templateData' => $this->templateData,
            ],
            static fn(mixed $value): bool => $value !== null,
        );
    }
}
