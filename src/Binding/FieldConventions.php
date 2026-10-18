<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Closure;
use FormCompose\Configuration\FieldConfig;

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
     * Supply conventional defaults while preserving explicit field settings.
     *
     */
    public function resolve(string $path, FieldConfig $config): FieldConfig
    {
        $config = $this->choices($path, $config);
        if ($config->type !== null) {
            return $config;
        }

        if (FieldPath::association($path) !== null) {
            return $config->type('select')->multiple($config->multiple ?? true);
        }

        if ($this->binding->context->isPrimaryKey($path)) {
            return $config->type('hidden');
        }

        if (str_ends_with($path, '_id')) {
            return $config->type('select');
        }

        return $config;
    }

    /**
     * Discover an iterable choice list without overriding explicit choices or input types.
     *
     */
    protected function choices(string $path, FieldConfig $config): FieldConfig
    {
        if ($config->hasChoices) {
            return $config;
        }

        if (!in_array($config->type ?? 'select', ['select', 'radio', 'multicheckbox'], true)) {
            return $config;
        }

        $enum = EnumChoices::discover($this->binding->context, $path);
        if ($enum !== null) {
            return $config->choices($enum);
        }

        if (!$this->viewVariable instanceof Closure) {
            return $config;
        }

        /** @var mixed $choices */
        $choices = ($this->viewVariable)(FieldPath::optionsVariable($path));
        if (is_iterable($choices)) {
            return $config->choices($choices);
        }

        return $config;
    }
}
