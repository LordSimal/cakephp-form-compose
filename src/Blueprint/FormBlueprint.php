<?php
declare(strict_types=1);

namespace FormCompose\Blueprint;

/**
 * Declares reusable fields and embeds for the current binding.
 *
 * Override configure() to declare fields, translate labels, populate choices,
 * and compose child blueprints using typed fluent builders. Services
 * belong in constructor dependencies; bound values remain in CakePHP's context.
 * A fresh blueprint and builder are created for each resolution, even for shared DI registrations.
 */
abstract class FormBlueprint
{
    /**
     * Customize declared fields when selected at bind() or rendered by blueprint().
     *
     * For example: $form->add('country_id')->choices($this->countries->choices());
     * Exceptions propagate to the caller; partial configuration is never bound.
     */
    public function configure(BlueprintBuilder $form, FormContext $context): void
    {
    }
}
