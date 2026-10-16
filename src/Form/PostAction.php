<?php
declare(strict_types=1);

namespace FormCompose\Form;

use InvalidArgumentException;

/**
 * Separates standalone action configuration from trigger attributes and locked data.
 *
 * PostActionRenderer consumes this immutable specification before opening a fresh
 * session. URL and method options always determine the protected form's target.
 */
final readonly class PostAction
{
    /** @var array<string, mixed> Opening options for the independent session. */
    public array $form;

    /** @var array<string, mixed> Validated flattened hidden field values. */
    public array $data;

    /** @var array<string, mixed> Trigger attributes and template presentation. */
    public array $options;

    public ?string $block;

    public ?string $confirm;

    /**
     * Validate action metadata before any session or view block is changed.
     *
     * @param array|string|null $url Cake route or URL; null uses the current request target.
     * @param array<string, mixed> $options Method, data, form, block, confirm, and trigger presentation.
     */
    public function __construct(public string $kind, array|string|null $url, array $options)
    {
        if (!in_array($kind, ['button', 'link'], true)) {
            throw new InvalidArgumentException('Post actions must render a button or a link.');
        }

        foreach (['form', 'data'] as $key) {
            if (array_key_exists($key, $options) && !is_array($options[$key])) {
                throw new InvalidArgumentException('The ' . $key . ' option must be an array.');
            }
        }

        /** @var array<string, mixed> $form */
        $form = $options['form'] ?? [];
        $method = PostActionSubmission::method($options['method'] ?? $form['method'] ?? 'post');

        $this->block = PostActionOptions::block($options['block'] ?? null);
        $this->confirm = PostActionOptions::confirm($options['confirm'] ?? null);
        $form['url'] = $url;
        $form['method'] = $method;
        unset($form['type']);
        if ($kind === 'link' && isset($options['target'])) {
            $form['target'] = $options['target'];
            unset($options['target']);
        }

        if ($kind === 'link' || $this->block !== null) {
            $form['hidden'] = true;
        }

        $form['data-form-compose-confirm'] = $this->confirm;

        if (isset($form['id'])) {
            $form['id'] = PostActionSubmission::id($form['id']);
        }

        $this->form = $form;
        /** @var array<string, mixed> $data */
        $data = $options['data'] ?? [];
        $this->data = PostActionSubmission::data($data);
        unset($options['form'], $options['data'], $options['method'], $options['block'], $options['confirm']);
        $this->options = PostActionOptions::trigger($options);
    }
}
