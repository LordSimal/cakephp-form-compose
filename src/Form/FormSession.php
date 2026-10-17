<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Binding\FieldResolver;
use FormCompose\Binding\FormBinding;
use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\Blueprint\BlueprintRenderer;
use FormCompose\Blueprint\FormContext;
use FormCompose\Blueprint\FormDefinition;
use FormCompose\Blueprint\RepeatedDefinition;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\ControlList;
use FormCompose\Rendering\ErrorSummaryRenderer;
use FormCompose\Rendering\Html;
use FormCompose\Rendering\RenderContext;
use FormCompose\Rendering\RenderNode;
use InvalidArgumentException;
use Throwable;
use function Cake\I18n\__;

/**
 * Coordinates the public API for building and rendering one bound form.
 *
 * Created by FormHelper::bind(). Uses the binding to resolve fields, the renderer
 * to produce themed markup, and the lifecycle to track submitted inputs and tokens.
 * A session can open once; render its inputs before closing it. Override
 * createFieldResolver() or createLifecycle() to supply specialized collaborators.
 *
 * ```php
 * $form = $this->Form->bind($article);
 * echo $form->open(), $form->control('title');
 * echo $form->submit('Save'), $form->close();
 * ```
 *
 * Keep complete-form and batch shortcuts alongside the explicit session operations.
 *
 * @mago-expect lint:too-many-methods
 */
class FormSession
{
    protected readonly FormLifecycle $lifecycle;

    protected readonly BlueprintRenderer $blueprintRenderer;

    protected readonly ErrorSummaryRenderer $summaries;

    /**
     * Create a session with binding, rendering, and blueprint construction dependencies.
     */
    public function __construct(
        public readonly FormBinding $binding,
        protected readonly RenderContext $rendering,
        protected readonly BlueprintFactory $blueprintFactory = new BlueprintFactory(),
        ?BlueprintRenderer $blueprintRenderer = null,
        ?ErrorSummaryRenderer $summaries = null,
    ) {
        $this->lifecycle = $this->createLifecycle();
        $this->blueprintRenderer = $blueprintRenderer ?? new BlueprintRenderer($rendering);
        $this->summaries = $summaries ?? new ErrorSummaryRenderer($rendering);
    }

    /**
     * Open, buffer a blueprint, arrange its summary/body/actions, and close this fresh session.
     *
     * A null blueprint uses binding defaults; without defaults the body is empty.
     * A null label translates Submit at rendering time; false omits the button.
     * The form/layout element receives the buffered body, summary, submit label,
     * and submitOptions while the session remains open for custom action buttons.
     * Select it with options['template'] or templates['layout']; blueprint layouts
     * continue to arrange the fields inside the body.
     * Returns all markup without echoing. Exceptions retain the existing lifecycle
     * state at the failed step; retry a complete form using a new session.
     *
     * @param array<string, mixed> $options Opening options and form attributes.
     * @param array<string, mixed> $submitOptions Button attributes and presentation options.
     */
    public function render(
        ?string $blueprint = null,
        array $options = [],
        string|Html|false|null $submit = null,
        array $submitOptions = [],
    ): string {
        $summary = $this->summaries->options($options, $this->binding->options);
        unset($options['errorSummary']);
        $html = $this->open($options);
        $body = $this->blueprint($blueprint);
        $summary = $summary === null ? '' : $this->errorSummary($summary);
        $state = $this->lifecycle->assertOpen();
        $html .= $this->rendering->renderer->render(
            'layout',
            new RenderNode(
                'form',
                $state->method,
                [
                    'form' => $this,
                    'state' => $state,
                    'body' => $body,
                    'summary' => $summary,
                    'submit' => $submit ?? __('Submit'),
                    'submitOptions' => $submitOptions,
                ],
                $state->presentation,
            ),
        );

        return $html . $this->close();
    }

    /**
     * Prepare and render the opening through the same theme system as fields.
     *
     * @param array<string, mixed> $attributes Form options and HTML attributes.
     */
    public function open(array $attributes = []): string
    {
        $state = $this->lifecycle->prepare($this->rendering->theme, $attributes);
        $html = $this->rendering->renderer->render(
            'open',
            new RenderNode(
                'form',
                $state->method,
                [
                    'form' => $this,
                    'state' => $state,
                    'attributes' => $state->attributes,
                    'hidden' => $state->hidden,
                ],
                $state->presentation,
            ),
        );
        $this->lifecycle->opened($state);

        return $html;
    }

    /**
     * Finalize security data and render closing markup before committing the closed state.
     */
    public function close(): string
    {
        $state = $this->lifecycle->assertOpen();
        try {
            $html = $this->rendering->renderer->render(
                'close',
                new RenderNode(
                    'form',
                    $state->method,
                    [
                        'form' => $this,
                        'state' => $state,
                        'hidden' => $this->lifecycle->tokens(),
                        'attributes' => $this->rendering->theme->attributes('form.close', $state->method),
                    ],
                    $state->presentation,
                ),
            );
        } catch (Throwable $throwable) {
            $this->lifecycle->cancelClose();
            throw $throwable;
        }

        $this->lifecycle->close();

        return $html;
    }

    /**
     * Resolve a field snapshot without emitting markup or registering submissions.
     *
     * @param array<string, mixed> $options Per-field overrides.
     */
    public function field(string $field, array $options = []): BoundField
    {
        return $this->createFieldResolver()->resolve($field, $options, $this->lifecycle);
    }

    /**
     * Render a complete field using the same bound state as manual composition.
     *
     * Set template to a CakePHP element name for this control's layout, or use
     * templates to override individual parts. Conflicting control overrides fail.
     * templateData supplies a separate data bag to the control and all its parts.
     *
     * @param array<string, mixed> $options Field options.
     */
    public function control(string $field, array $options = []): string
    {
        return $this->field($field, $options)->control();
    }

    /**
     * Render ordered controls without a wrapper, committing inputs as one batch.
     *
     * Entries are indexed paths or path => options.
     * Failed batches roll back their input registrations, like blueprint layouts.
     * Each control uses this session's existing defaults and extension points.
     *
     * @param array<array-key, mixed> $fields Batch control declarations.
     * @throws \InvalidArgumentException For invalid batch declarations.
     */
    public function controls(array $fields): string
    {
        $definitions = ControlList::normalize($fields);

        return $this->lifecycle->inputs->render(function () use ($definitions): string {
            $html = '';
            foreach ($definitions as $definition) {
                $html .= $this->control($definition->path, $definition->options);
            }

            return $html;
        });
    }

    /**
     * Render a reusable field collection here, through the normal control templates.
     *
     * With no argument, render the blueprint selected at bind(). An explicitly
     * supplied blueprint alias or class stays local to this call and
     * does not replace defaults. Blueprints use this session's factory and context.
     * The form must be open; a session without a selected blueprint renders an empty string.
     * at mounts relative fields at a root context path. Blueprint layout options
     * stay local.
     *
     * @param array<string, mixed> $options Blueprint presentation overrides.
     */
    public function blueprint(
        ?string $blueprint = null,
        ?string $at = null,
        array $options = [],
    ): string {
        $this->lifecycle->assertOpen();
        $context = FormContext::fromBinding($this->binding);
        if ($at !== null) {
            $context = $context->at($at);
        }

        $definition = $blueprint === null
            ? $this->binding->definition ?? new FormDefinition([])
            : $this->blueprintFactory->compile($blueprint, $context);

        return $this->renderDefinition($definition, $at, $options);
    }

    /**
     * Render compiled layout state without resolving its blueprint again.
     *
     * @param array<string, mixed> $options Local presentation overrides.
     * @internal Used by scoped blueprint views.
     */
    public function renderDefinition(FormDefinition $definition, ?string $at = null, array $options = []): string
    {
        $this->lifecycle->assertOpen();

        return $this->lifecycle->inputs->render(
            fn(): string => $this->blueprintRenderer->render($this, $definition, $at ?? '', $options),
        );
    }

    /**
     * Render a repeated blueprint at an association path in this open session.
     *
     * @param list<int|string>|null $keys Null infers row keys from the binding's value sources.
     * @param array<string, mixed> $options Wrapper presentation, label, and collection feedback.
     */
    public function embedMany(
        string $path,
        string $blueprint,
        ?array $keys = null,
        array $options = [],
    ): string {
        $this->lifecycle->assertOpen();
        $repeat = new RepeatedDefinition($path, $blueprint, $keys);
        $context = FormContext::fromBinding($this->binding);
        $compiled = $this->blueprintFactory->repeated($repeat, $context);

        return $this->renderRepeated($path, $compiled, $options);
    }

    /**
     * Render compiled rows at their existing absolute mount.
     *
     * @param array<string, mixed> $options Occurrence overrides.
     * @internal Used by scoped blueprint views without re-running hooks.
     */
    public function renderRepeated(string $path, RepeatedDefinition $compiled, array $options = []): string
    {
        $this->lifecycle->assertOpen();
        $compiled = new RepeatedDefinition(
            $path,
            $compiled->blueprint,
            $compiled->keys,
            $compiled->options,
            $compiled->rows,
        );

        return $this->lifecycle->inputs->render(fn(): string => $this->blueprintRenderer->renderMany(
            $this,
            $compiled,
            $options,
            $this->lifecycle->inputs,
        ));
    }

    /**
     * Summarize successfully rendered controls and repeated sections in this open form.
     *
     * Render or buffer the body first; inspecting fields never contributes entries.
     * @param array<string, mixed> $options Title and template/attribute presentation.
     */
    public function errorSummary(array $options = []): string
    {
        $this->lifecycle->assertOpen();

        return $this->summaries->render($this, $this->lifecycle->inputs, $options);
    }

    /**
     * Register a handwritten feedback element after emitting its prepared attributes.
     *
     * Use field->attributes($part) on the element, then registerFeedback($field, $part).
     * This reserves its configured ID without rendering or registering submissions.
     * Do not also render that element with the field's help() or errors() method.
     *
     * @param array<string, mixed> $attributes Local feedback attributes used in the markup.
     * @throws \InvalidArgumentException For parts other than help and errors.
     */
    public function registerFeedback(BoundField $field, string $part, array $attributes = []): void
    {
        if (!in_array($part, ['help', 'errors'], true)) {
            throw new InvalidArgumentException('Only handwritten help and errors parts can be registered.');
        }

        $this->lifecycle->assertOpen();
        $this->lifecycle->inputs->render(static fn(): string => '', $field->attributes($part, $attributes));
    }

    /**
     * Render a themed button and register its optional submission only after rendering succeeds.
     *
     * @param array<string, mixed> $attributes Button attributes and optional templates, variant, or templateData.
     */
    public function submit(string|Html $label, array $attributes = []): string
    {
        $this->lifecycle->assertOpen();
        $options = array_intersect_key($attributes, array_flip(['templates', 'variant', 'templateData']));
        unset($attributes['templates'], $attributes['variant'], $attributes['templateData']);
        $type = (string)($attributes['type'] ?? 'submit');
        $bag = $this->rendering->theme->attributes('button', $type)->merge(['type' => 'submit'])->merge($attributes);
        $html = $this->rendering->renderer->render(
            'control',
            new RenderNode(
                'button',
                $type,
                [
                    'form' => $this,
                    'label' => $label,
                    'attributes' => $bag,
                ],
                $options,
            ),
        );
        $this->lifecycle->button($bag);

        return $html;
    }

    /**
     * Render prepared infrastructure fields through themed hidden-input elements.
     *
     * @param list<\FormCompose\Rendering\Attributes> $fields Method, CSRF, or final protection fields.
     * @param array<string, mixed> $presentation Form template and variant options.
     */
    public function hiddenFields(array $fields, array $presentation = []): string
    {
        $html = '';
        foreach ($fields as $field) {
            $attributes = $this->rendering->theme->attributes('form.hidden', 'hidden')->merge($field->values);
            $html .= $this->rendering->renderer->render(
                'hidden',
                new RenderNode(
                    'form',
                    'hidden',
                    [
                        'attributes' => $attributes,
                    ],
                    $presentation,
                ),
            );
        }

        return $html;
    }

    /**
     * Create lifecycle coordination for this session before opening the form.
     */
    protected function createLifecycle(): FormLifecycle
    {
        return new FormLifecycle($this->binding);
    }

    /**
     * Create the resolver used by field() and control().
     */
    protected function createFieldResolver(): FieldResolver
    {
        return new FieldResolver($this->binding, $this->rendering);
    }
}
