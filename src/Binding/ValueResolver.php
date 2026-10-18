<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use BackedEnum;
use FormCompose\Configuration\FieldConfig;
use InvalidArgumentException;

/**
 * Chooses a field value from explicit configuration, request data, and the CakePHP context.
 *
 * An explicit configured value wins, including null. Otherwise the form's valueSources
 * are consulted in order (data then context by default), followed by the field's
 * default. Request lookups preserve explicit empty values; backed enums are
 * converted to their scalar value before being stored in FieldState. Subclasses
 * can override fromSources() while retaining explicit-value precedence in resolve().
 */
class ValueResolver
{
    /**
     * Bind a value resolver to a form's request and context.
     */
    public function __construct(protected readonly FormBinding $binding)
    {
    }

    /**
     * Read explicit values, configured sources, and defaults in order.
     *
     */
    public function resolve(string $path, FieldConfig $config): mixed
    {
        /** @var mixed $value */
        $value = $config->hasValue ? $config->value : $this->fromSources($path, $config);

        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * Consult request data or context without treating false, zero, and null as absence.
     *
     */
    protected function fromSources(string $path, FieldConfig $config): mixed
    {
        $sources = $this->binding->config->valueSources;
        if ($sources === [] || array_diff($sources, ['data', 'context', 'query']) !== []) {
            throw new InvalidArgumentException('Value sources must contain data, context, or query.');
        }

        /** @var mixed $default */
        $default = $config->defaultValue;
        foreach ($sources as $source) {
            if ($source === 'context') {
                /** @var mixed $value */
                $value = $this->binding->context->val($path, [
                    'default' => $default,
                    'schemaDefault' => $config->schemaDefault,
                ]);
                if ($value !== null) {
                    return $value;
                }

                continue;
            }

            /** @var mixed $data */
            $data = $source === 'query'
                ? $this->binding->request->getQueryParams()
                : $this->binding->request->getData();
            $lookup = PathValue::lookup(is_array($data) ? $data : [], $path);
            if ($lookup->found) {
                return $lookup->value;
            }
        }

        return $default;
    }
}
