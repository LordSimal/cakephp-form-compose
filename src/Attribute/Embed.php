<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Declares a reusable blueprint at a relative association path, in presentation order.
 *
 * #[Embed('author', AuthorForm::class)] qualifies the child's email as author.email.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Embed
{
    /**
     * Capture the mount path and a blueprint alias or class name.
     */
    public function __construct(public string $path, public string $definition)
    {
        new FieldDefinition($path);
        if ($definition === '' || trim($definition) !== $definition) {
            throw new InvalidArgumentException(
                'Embedded blueprint names must be nonempty and have no surrounding whitespace.',
            );
        }
    }
}
