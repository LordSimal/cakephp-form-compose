<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Declares a reusable blueprint at a relative association path, in presentation order.
 *
 * #[Embed('author', 'author')] mounts the discovered alias and qualifies email as author.email.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Embed
{
    /**
     * Capture the mount path and a blueprint alias or class name.
     *
     * @param array<string, mixed> $options Presentation overrides for this occurrence.
     */
    public function __construct(public string $path, public string $blueprint, public array $options = [])
    {
        new FieldDefinition($path);
        if ($blueprint === '' || trim($blueprint) !== $blueprint) {
            throw new InvalidArgumentException(
                'Embedded blueprint names must be nonempty and have no surrounding whitespace.',
            );
        }
    }
}
