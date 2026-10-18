<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use FormCompose\Configuration\FieldConfig;
use InvalidArgumentException;

/**
 * Captures validated control lookup and submitted/DOM identity for a resolved path.
 *
 * @internal Shared by ordinary and composite child resolution.
 */
final readonly class FieldIdentity
{
    public string $name;

    public string $id;

    /**
     * Derive bracket names and collision-safe IDs, honoring explicit field options.
     *
     */
    public function __construct(string $path, public string $type, string $formId, FieldConfig $config)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/D', $type)) {
            throw new InvalidArgumentException('Invalid control type.');
        }

        $parts = explode('.', $path);
        $first = array_shift($parts);
        $this->name = $config->name ?? $first . ($parts === [] ? '' : '[' . implode('][', $parts) . ']');
        // Preserve path separators and reserve hyphens for generated part suffixes.
        $this->id = $config->id ?? $formId . '-' . str_replace('-', ':2d', $path);
        if ($this->id === '' || !preg_match('/^[a-zA-Z0-9_.:-]+(?:\[[a-zA-Z0-9_.:-]*\])*$/D', $this->name)) {
            throw new InvalidArgumentException('Use a nonempty ID and a valid bracket-notation input name.');
        }
    }
}
