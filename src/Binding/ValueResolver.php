<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use BackedEnum;
use InvalidArgumentException;

/**
 * Resolves submitted and contextual values while preserving explicit empty states.
 */
final readonly class ValueResolver
{
    /**
     * Bind a value resolver to a form's request and context.
     */
    public function __construct(private FormBinding $binding)
    {
    }

    /**
     * Read explicit values, configured sources, and defaults in order.
     *
     * @param array<string, mixed> $options Field value options.
     */
    public function resolve(string $path, array $options): mixed
    {
        /** @var mixed $value */
        $value = array_key_exists('value', $options) ? $options['value'] : $this->fromSources($path, $options);

        return $value instanceof BackedEnum ? $value->value : $value;
    }

    /**
     * Consult request data or context without treating false, zero, and null as absence.
     *
     * @param array<string, mixed> $options Field value options.
     */
    private function fromSources(string $path, array $options): mixed
    {
        /** @var list<string> $sources */
        $sources = $this->binding->options['valueSources'] ?? ['data', 'context'];
        if ($sources === [] || array_diff($sources, ['data', 'context', 'query']) !== []) {
            throw new InvalidArgumentException('Value sources must contain data, context, or query.');
        }
        /** @var mixed $default */
        $default = $options['default'] ?? null;
        foreach ($sources as $source) {
            if ($source === 'context') {
                /** @var mixed $value */
                $value = $this->binding->context->val($path, [
                    'default' => $default,
                    'schemaDefault' => $options['schemaDefault'] ?? true,
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
