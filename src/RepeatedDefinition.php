<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Declares one row prototype and retains its independently compiled row tree.
 *
 * Blueprint compilation creates these records from EmbedMany or builder calls.
 * Unbound declarations have null rows; compilation returns a new snapshot with
 * rows mounted at their original keys. Request-dependent snapshots are never cached.
 *
 * @internal Compiled blueprint composition metadata.
 */
final readonly class RepeatedDefinition
{
    /**
     * Select a row blueprint, optional keys, and occurrence presentation.
     *
     * @param list<int|string>|null $keys Null infers keys from bound value sources; [] renders no rows.
     * @param array<string, mixed> $options Template, templates, templateData, variant, label, errors, attributes, or partAttributes.
     * @param \FormCompose\FormDefinition|null $rows Compiled row embeds; null for a reusable declaration.
     */
    public function __construct(
        public string $path,
        public string $definition,
        public ?array $keys = null,
        public array $options = [],
        public ?FormDefinition $rows = null,
    ) {
        new FieldDefinition($path);
        if ($definition === '' || trim($definition) !== $definition) {
            throw new InvalidArgumentException(
                'Repeated blueprint names must be nonempty and have no surrounding whitespace.',
            );
        }

        if ($keys !== null) {
            self::validateKeys($keys);
        }
    }

    /**
     * Validate selected or inferred keys before any row blueprint hooks run.
     *
     * @param list<int|string> $keys Original collection keys.
     */
    public static function validateKeys(array $keys): void
    {
        $seen = [];
        /** @var mixed $key */
        foreach ($keys as $key) {
            if (!is_int($key) && !is_string($key) || !preg_match('/^[a-zA-Z0-9_-]+$/D', (string)$key)) {
                throw new InvalidArgumentException('Repeated row keys must be valid field-path segments.');
            }

            if (isset($seen[(string)$key])) {
                throw new InvalidArgumentException('Repeated row keys must be unique.');
            }

            $seen[(string)$key] = true;
        }
    }
}
