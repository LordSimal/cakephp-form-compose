<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Binding\FieldResolver;
use FormCompose\Form\FormSession;

/**
 * Applies contact-field conventions while retaining the normal form lifecycle.
 */
class ContactFormSession extends FormSession
{
    /**
     * Use the contact-specific type and value inference rules.
     */
    protected function createFieldResolver(): FieldResolver
    {
        return new ContactFieldResolver($this->binding, $this->rendering);
    }
}
