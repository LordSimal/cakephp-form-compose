<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Form\Field\FieldState;
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
        if (in_array($part, ['help', 'errors'], true) && array_key_exists('id', $extra)) {
            throw new InvalidArgumentException('Configure help and error IDs using field partAttributes.');
        }

        $values = match ($part) {
            'input' => ['name' => $state->name, 'id' => $state->id] + $state->constraints + $this->descriptions($state),
            'label' => $state->grouped() ? [] : ['for' => $state->id],
            'help' => ['id' => $state->id . '-help'],
            'errors' => ['id' => $state->id . '-errors'],
            default => [],
        };
        $parts = $state->config->presentation->partAttributes;
        $configured = $parts[$part] ?? [];
        if ($part === 'input') {
            $input = $state->config->presentation->attributes;
            $configured = new Attributes($configured)->merge($input)->values;
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
            $help = $this->resolve($state, 'help', []);
            if ($help->has('id')) {
                $ids[] = (string)$help->values['id'];
            }
        }

        if ($state->hasErrors()) {
            $errors = $this->resolve($state, 'errors', []);
            if ($errors->has('id')) {
                $ids[] = (string)$errors->values['id'];
            }

            $attributes['aria-invalid'] = 'true';
        }

        if ($ids !== []) {
            $attributes['aria-describedby'] = implode(' ', $ids);
        }

        return $attributes;
    }
}
