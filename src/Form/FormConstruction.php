<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Cake\Core\Configure;
use FormCompose\Binding\FormBinding;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\Theme;
use InvalidArgumentException;

/**
 * Converts form binding and opening options into a prepared FormState.
 *
 * Resolves the action URL, effective HTTP method, multipart encoding, theme
 * attributes, and initial CSRF/method hidden inputs. New contexts default to POST
 * and existing records to PUT. FormLifecycle requests this data before FormSession
 * renders the opening template and commits the open state. Subclasses can override
 * hidden() to specialize initial infrastructure fields while reusing prepare().
 */
class FormConstruction
{
    /**
     * Resolve HTML attributes, multipart encoding, method overrides, and CSRF fields.
     *
     * @param array<string, mixed> $extra Per-opening options.
     */
    public function prepare(FormBinding $binding, Theme $theme, array $extra): FormState
    {
        $options = array_replace($binding->options, $extra);
        $defaultMethod = $binding->context->isCreate() ? 'post' : 'put';
        $type = strtolower((string)($options['type'] ?? $defaultMethod));
        $method = strtolower((string)($options['method'] ?? ($type === 'file' ? $defaultMethod : $type)));
        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'], true)) {
            throw new InvalidArgumentException('Unsupported form method.');
        }
        $url = ActionUrl::resolve($binding, $options);
        $presentation = array_intersect_key($options, array_flip(['templates', 'variant', 'templateData']));
        /** @var string $encoding */
        $encoding = $options['encoding'] ?? Configure::read('App.encoding', 'UTF-8');
        /** @var string|null $enctype */
        $enctype = $options['enctype'] ?? ($type === 'file' ? 'multipart/form-data' : null);
        unset(
            $options['url'],
            $options['type'],
            $options['method'],
            $options['encoding'],
            $options['enctype'],
            $options['templates'],
            $options['variant'],
            $options['templateData'],
            $options['valueSources'],
        );
        $attributes = $theme->attributes('form.open', $method)
            ->merge($options)
            ->replace([
                'id' => $binding->id,
                'action' => $url['attribute'],
                'method' => $method === 'get' ? 'get' : 'post',
                'accept-charset' => strtolower($encoding),
                'enctype' => $enctype === null ? null : strtolower($enctype),
            ]);

        return new FormState($attributes, $method, $url['signed'], $this->hidden($binding, $method), $presentation);
    }

    /**
     * Describe method and CSRF inputs separately from their visual presentation.
     *
     * @return list<\FormCompose\Rendering\Attributes>
     */
    protected function hidden(FormBinding $binding, string $method): array
    {
        $hidden = [];
        if (!in_array($method, ['get', 'post'], true)) {
            $hidden[] = new Attributes(['type' => 'hidden', 'name' => '_method', 'value' => strtoupper($method)]);
        }
        /** @var string|null $csrf */
        $csrf = $binding->request->getAttribute('csrfToken');
        if ($method !== 'get' && $csrf !== null) {
            $hidden[] = new Attributes(['type' => 'hidden', 'name' => '_csrfToken', 'value' => $csrf]);
        }

        return $hidden;
    }
}
