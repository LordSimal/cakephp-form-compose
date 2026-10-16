<?php
declare(strict_types=1);

namespace FormCompose;

use FormCompose\Field\FieldDefinition;
use InvalidArgumentException;

/**
 * Declares one row prototype and retains its independently compiled row tree.
 *
 * Add this immutable declaration to a FormDefinition or use FormBuilder::embedMany().
 * Unbound declarations have null rows; compilation returns a new snapshot with
 * rows mounted at their original keys. Request-dependent snapshots are never cached.
 */
final readonly class RepeatedDefinition
{
    /**
     * Select a row blueprint/definition, optional keys, and occurrence presentation.
     *
     * @param list<int|string>|null $keys Null infers keys from bound value sources; [] renders no rows.
     * @param array<string, mixed> $options Template, templates, templateData, variant, label, errors, attributes, or partAttributes.
     * @param \FormCompose\FormDefinition|null $rows Compiled row embeds; null for a reusable declaration.
     */
    public function __construct(
        public string $path,
        public FormDefinition|string $definition,
        public ?array $keys = null,
        public array $options = [],
        public ?FormDefinition $rows = null,
    ) {
        new FieldDefinition($path);
        if (is_string($definition) && ($definition === '' || trim($definition) !== $definition)) {
            throw new InvalidArgumentException(
                'Repeated blueprint names must be nonempty and have no surrounding whitespace.',
            );
        }

        if ($keys !== null) {
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
}
