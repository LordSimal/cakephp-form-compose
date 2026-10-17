<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use Cake\View\Form\EntityContext;
use Closure;

/**
 * Applies CakePHP's association and choice-list conventions before field binding.
 *
 * _ids paths default to multiple selects, foreign keys to selects, and context
 * primary keys to hidden inputs. Registered enum schema types supply choices
 * before matching iterable view variables when none are explicitly configured.
 * Association errors use the collection's
 * property path while values and submitted names retain the original _ids path.
 * Override choices() to specialize discovery without replacing context binding.
 */
class FieldConventions
{
    /**
     * Accept explicit context and an optional live lookup of CakePHP view variables.
     *
     * @param \Closure(string): mixed|null $viewVariable View variable lookup, without query execution.
     */
    public function __construct(
        protected readonly FormBinding $binding,
        protected readonly ?Closure $viewVariable = null,
    ) {
    }

    /**
     * Supply conventional defaults while preserving explicitly configured options.
     *
     * @param array<string, mixed> $options Merged field configuration.
     * @return array<string, mixed>
     */
    public function resolve(string $path, array $options): array
    {
        $options = $this->choices($path, $options);
        if (isset($options['type'])) {
            return $options;
        }

        if (FieldPath::association($path) !== null) {
            return $options + ['type' => 'select', 'multiple' => true];
        }

        if ($this->binding->context->isPrimaryKey($path)) {
            return $options + ['type' => 'hidden'];
        }

        if (str_ends_with($path, '_id')) {
            $options['type'] = 'select';
        }

        return $options;
    }

    /**
     * Flatten context errors, mapping collection selectors to their association.
     *
     * @return list<string>
     */
    public function messages(string $path): array
    {
        $association = FieldPath::association($path);
        $errors = $this->binding->context->error($association ?? $path);
        if ($association !== null && $this->binding->context instanceof EntityContext) {
            $owner = $this->binding->context->entity(explode('.', $association));
            if ($owner instanceof EntityInterface) {
                $errors = $owner->getError(basename(str_replace('.', '/', $association)));
            }
        }

        return array_map(
            static fn(mixed $message): string => (string)$message,
            array_values(Hash::flatten($errors)),
        );
    }

    /**
     * Read the context's required-validation text for the actual validated property.
     */
    public function requiredMessage(string $path): ?string
    {
        return $this->binding->context->getRequiredMessage(FieldPath::association($path) ?? $path);
    }

    /**
     * Discover an iterable choice list without overriding explicit choices or input types.
     *
     * @param array<string, mixed> $options Merged field configuration.
     * @return array<string, mixed>
     */
    protected function choices(string $path, array $options): array
    {
        if (array_key_exists('options', $options)) {
            return $options;
        }

        if (!in_array($options['type'] ?? 'select', ['select', 'radio', 'multicheckbox'], true)) {
            return $options;
        }

        $enum = EnumChoices::discover($this->binding->context, $path);
        if ($enum !== null) {
            return $options + ['options' => $enum];
        }

        if (!$this->viewVariable instanceof Closure) {
            return $options;
        }

        /** @var mixed $choices */
        $choices = ($this->viewVariable)(FieldPath::optionsVariable($path));
        if (is_iterable($choices)) {
            $options['options'] = $choices;
        }

        return $options;
    }
}
