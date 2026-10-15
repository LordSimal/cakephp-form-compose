<?php
declare(strict_types=1);

namespace FormCompose;

/**
 * Defines a reusable form's static declarations and optional runtime configuration.
 *
 * Add class-level Field attributes for defaults. Override configure() to populate
 * choices, change options, or add/remove fields for the current binding. Services
 * belong in constructor dependencies; bound values remain in CakePHP's context.
 * A fresh blueprint and builder are created for each binding, even for shared DI registrations.
 */
abstract class FormBlueprint
{
    /**
     * Customize the declared fields once per binding, before any rendering.
     *
     * For example: $form->with('country_id', ['options' => $this->countries->choices()]);
     * Exceptions propagate to the caller; partial configuration is never bound.
     */
    public function configure(FormBuilder $form, FormContext $context): void
    {
    }
}
