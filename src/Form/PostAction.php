<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;
use FormCompose\Configuration\FormConfig;
use FormCompose\Configuration\PresentationConfig;
use InvalidArgumentException;

/** Validated standalone action metadata, prepared before any session or view block changes. */
final readonly class PostAction
{
    public FormConfig $form;

    public ButtonConfig $button;

    /** @var array<string, mixed> Validated, flattened locked payload. */
    public array $data;

    public PresentationConfig $presentation;

    public ?string $block;

    public ?string $confirm;

    /** @param array|string|null $url Cake route or URL; null uses the current request target. */
    public function __construct(public string $kind, array|string|null $url, ActionConfig $config)
    {
        if (!in_array($kind, ['button', 'link'], true)) {
            throw new InvalidArgumentException('Post actions must render a button or a link.');
        }

        $this->block = PostActionOptions::block($config->block);
        $this->confirm = PostActionOptions::confirm($config->confirm);
        $presentation = $config->presentation;
        PostActionOptions::trigger($presentation->attributes);
        $attributes = $presentation->attributes;
        $form = $config
            ->form
            ->withUrl($url)
            ->withMethod(PostActionSubmission::method($config->method ?? $config->form->method ?? 'post'));
        if ($kind === 'link' && isset($attributes['target'])) {
            $form = $form->withPresentation($form->presentation->withAttributes(['target' => $attributes['target']]));
            unset($attributes['target']);
            $presentation = $presentation->replaceAttributes($attributes);
        }

        $form = $form->withPresentation($form->presentation->withAttributes([
            'hidden' => $kind === 'link' || $this->block !== null ? true : null,
            'data-form-compose-confirm' => $this->confirm,
        ]));
        if ($form->id !== null) {
            PostActionSubmission::id($form->id);
        }

        if ($kind === 'link' && ($config->name !== null || $config->value !== null || $config->disabled)) {
            throw new InvalidArgumentException('Button name, value, and disabled settings require a post button.');
        }

        $this->button = new ButtonConfig(name: $config->name, value: $config->value)->withDisabled($config->disabled)
            ->withPresentation($presentation);
        $this->form = $form;
        $this->data = PostActionSubmission::data($config->data);
        $this->presentation = $presentation;
    }
}
