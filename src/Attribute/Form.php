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
     * Declare an application- or plugin-qualified form alias.
     */
    public function __construct(public string $name)
    {
        if ($name === '' || trim($name) !== $name) {
            throw new InvalidArgumentException('Form aliases must be nonempty and have no surrounding whitespace.');
        }
    }
}
