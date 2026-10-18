<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use InvalidArgumentException;

/** Names a reusable fluent blueprint for discovery through CakePHP's AttributeResolver. */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Form
{
    /** Declare a blueprint alias; configure fields and presentation in its hook. */
    public function __construct(public string $name)
    {
        if ($name === '' || trim($name) !== $name) {
            throw new InvalidArgumentException('Form aliases must be nonempty and have no surrounding whitespace.');
        }
    }
}
