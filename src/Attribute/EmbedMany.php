<?php
declare(strict_types=1);

namespace FormCompose\Attribute;

use Attribute;
use FormCompose\Blueprint\RepeatedDefinition;

/**
 * Repeats a child blueprint at an association path, preserving its row keys.
 *
 * #[EmbedMany('comments', CommentForm::class)] mounts each row independently.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class EmbedMany
{
    /**
     * Select a row blueprint and optional keys and presentation for this occurrence.
     *
     * @param list<int|string>|null $keys Null infers keys from the binding.
     * @param array<string, mixed> $options Repeated-embed presentation options.
     */
    public function __construct(
        public string $path,
        public string $blueprint,
        public ?array $keys = null,
        public array $options = [],
    ) {
        new RepeatedDefinition($path, $blueprint, $keys, $options);
    }
}
