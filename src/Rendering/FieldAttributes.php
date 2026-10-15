<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Field\FieldState;
use InvalidArgumentException;

/**
 * Builds attribute bags for a field's input, label, help, errors, or container.
 *
 * BoundField uses this to combine theme defaults, resolved field metadata, field
 * configuration, and local attributes. It adds label/help/error associations and
 * ARIA feedback, and rejects identity changes through input attribute bags;
 * name, id, type, and value are configured as field options.
 */
class FieldAttributes
{
    /**
     * Use theme defaults as the starting point for each field part.
     */
    public function __construct(protected readonly Theme $theme)
    {
    }

    /**
     * Merge theme defaults, field metadata, configuration, and local attributes.
     *
     * @param array<string, mixed> $extra Local overrides.
     */
    public function resolve(FieldState $state, string $part, array $extra): Attributes
    {
        $values = match ($part) {
            'input' => ['name' => $state->name, 'id' => $state->id] + $state->constraints + $this->descriptions($state),
            'label' => $state->grouped() ? [] : ['for' => $state->id],
            'help' => ['id' => $state->id . '-help'],
            'errors' => ['id' => $state->id . '-errors'],
            default => [],
        };
        /** @var array<string, array<string, mixed>> $parts */
        $parts = $state->options['partAttributes'] ?? [];
        $configured = $parts[$part] ?? [];
        if ($part === 'input') {
            /** @var array<string, mixed> $input */
            $input = $state->options['attributes'] ?? [];
            $configured = array_replace($configured, $input);
            $reserved = array_intersect(array_keys($configured + $extra), [
                'name',
                'id',
                'type',
                'value',
                'escape',
                'secure',
                'options',
                'val',
            ]);
            if ($reserved !== []) {
                throw new InvalidArgumentException('Configure input identity and behavior using field options.');
            }
        }

        return $this->theme->attributes($part, $state->type)->merge($values)->merge($configured)->merge($extra);
    }

    /**
     * Associate only help and error parts enabled by presentation configuration.
     *
     * @return array<string, string>
     */
    protected function descriptions(FieldState $state): array
    {
        if ($state->type === 'hidden') {
            return [];
        }

        $ids = [];
        $attributes = [];
        if ($state->hasHelp()) {
            $ids[] = $state->id . '-help';
        }

        if ($state->hasErrors()) {
            $ids[] = $state->id . '-errors';
            $attributes['aria-invalid'] = 'true';
        }

        if ($ids !== []) {
            $attributes['aria-describedby'] = implode(' ', $ids);
        }

        return $attributes;
    }
}
