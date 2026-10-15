<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Binding\FormBinding;
use FormCompose\Field\BoundField;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Theme;
use FormCompose\Security\FormProtection;
use LogicException;

/**
 * Owns the state machine and submission bookkeeping independently of rendering.
 */
final class FormLifecycle
{
    private ?FormState $state = null;

    private FormPhase $phase = FormPhase::Pending;

    /** @var array<string, true> */
    private array $rendered = [];

    private readonly FormProtection $protection;

    /**
     * Create per-form security coordination without constructing a view helper.
     */
    public function __construct(private readonly FormBinding $binding)
    {
        $this->protection = new FormProtection($binding->request);
    }

    /**
     * Prepare an opening without committing the lifecycle before rendering succeeds.
     *
     * @param array<string, mixed> $options Opening options and HTML attributes.
     */
    public function prepare(Theme $theme, array $options): FormState
    {
        if ($this->state !== null) {
            throw new LogicException('A form session can only be opened once.');
        }

        return FormConstruction::prepare($this->binding, $theme, $options);
    }

    /**
     * Commit the opening and start requested tamper protection after successful rendering.
     */
    public function opened(FormState $state): void
    {
        $this->protection->start($state);
        $this->state = $state;
        $this->phase = FormPhase::Open;
    }

    /**
     * Require an active lifecycle and return its resolved metadata.
     */
    public function assertOpen(): FormState
    {
        if ($this->state === null || $this->phase !== FormPhase::Open) {
            throw new LogicException('Expected an open form; current phase is ' . $this->phase->name . '.');
        }

        return $this->state;
    }

    /**
     * Reject duplicate input identities and file controls outside a multipart form.
     */
    public function assertInput(BoundField $field): void
    {
        $state = $this->assertOpen();
        if (isset($this->rendered[$field->state->id])) {
            throw new LogicException('An input with this ID has already been rendered: ' . $field->state->id);
        }
        if ($field->state->type === 'file' && !$state->multipart()) {
            throw new LogicException('File inputs require a multipart form.');
        }
    }

    /**
     * Commit the actual submitted names only after an input renderer has succeeded.
     *
     * @param list<\FormCompose\Field\Submission> $submissions Actual submitted inputs.
     */
    public function register(BoundField $field, array $submissions): void
    {
        $this->assertInput($field);
        $this->protection->register($submissions);
        $this->rendered[$field->state->id] = true;
    }

    /**
     * Register a successfully rendered named button as an optional submitted control.
     */
    public function button(Attributes $attributes): void
    {
        $this->assertOpen();
        if (isset($attributes->values['name'])) {
            $this->protection->optional((string)$attributes->values['name']);
        }
    }

    /**
     * Prepare final tokens without closing the lifecycle before templates succeed.
     *
     * @return list<\FormCompose\Rendering\Attributes>
     */
    public function tokens(): array
    {
        $fields = $this->protection->fields($this->assertOpen());
        $this->phase = FormPhase::Finalizing;

        return $fields;
    }

    /**
     * Commit the successfully rendered closing.
     */
    public function close(): void
    {
        $this->phase = FormPhase::Closed;
    }

    /**
     * Restore input collection after a closing template fails.
     */
    public function cancelClose(): void
    {
        $this->phase = FormPhase::Open;
    }
}
