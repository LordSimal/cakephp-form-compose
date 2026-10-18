<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Cake\Core\Configure;
use FormCompose\Binding\FormBinding;
use FormCompose\Configuration\FormConfig;
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
     */
    public function prepare(FormBinding $binding, Theme $theme, FormConfig $extra): FormState
    {
        $config = $binding->config->merge($extra);
        $method = strtolower($config->method ?? ($binding->context->isCreate() ? 'post' : 'put'));
        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'], true)) {
            throw new InvalidArgumentException('Unsupported form method.');
        }

        $url = ActionUrl::resolve($binding, $config);
        $presentation = $config->presentation;
        /** @var mixed $encoding */
        $encoding = $config->encoding ?? Configure::read('App.encoding', 'UTF-8');
        assert(is_string($encoding), 'The application encoding must be a string.');
        $enctype = $config->enctype ?? ($config->multipart ? 'multipart/form-data' : null);
        $attributes = $theme->attributes('form.open', $method)
            ->merge($presentation->attributes)
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
