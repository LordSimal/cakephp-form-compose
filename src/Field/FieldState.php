<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Html;

/**
 * Immutable resolved metadata usable without any HTML renderer.
 */
final readonly class FieldState
{
    /**
     * Capture field identity, value, configuration, constraints, and validation feedback.
     *
     * @param array{name: string, id: string, messages: list<string>, constraints: array<string, mixed>, options: array<string, mixed>} $data Presentation metadata.
     */
    public function __construct(public string $path, public string $type, public mixed $value, array $data)
    {
        $this->name = $data['name'];
        $this->id = $data['id'];
        $this->messages = $data['messages'];
        $this->constraints = $data['constraints'];
        $this->options = $data['options'];
    }

    public string $name;

    public string $id;

    /** @var list<string> */
    public array $messages;

    /** @var array<string, mixed> */
    public array $constraints;

    /** @var array<string, mixed> */
    public array $options;

    /**
     * Identify controls that require a group legend.
     */
    public function grouped(): bool
    {
        return in_array($this->type, ['radio', 'multicheckbox'], true);
    }

    /**
     * Determine whether help markup is part of this field's presentation.
     */
    public function hasHelp(): bool
    {
        return isset($this->options['help']) && $this->options['help'] !== false && $this->options['help'] !== '';
    }

    /**
     * Determine whether validation messages should be rendered and referenced.
     */
    public function hasErrors(): bool
    {
        return ($this->options['errors'] ?? true) !== false && $this->messages !== [];
    }

    /**
     * Resolve and escape label content, unless explicitly marked as trusted HTML.
     */
    public function labelText(): string
    {
        /** @var string|Html|false $label */
        $label = $this->options['label'] ?? ucfirst(str_replace(
            '_',
            ' ',
            basename(str_replace('.', '/', $this->path)),
        ));

        return Html::text($label instanceof Html ? $label : (string)$label);
    }

    /**
     * Resolve escaped or explicitly trusted help content.
     */
    public function helpText(): string
    {
        /** @var string|Html|false $help */
        $help = $this->options['help'] ?? '';

        return Html::text($help instanceof Html ? $help : (string)$help);
    }
}
