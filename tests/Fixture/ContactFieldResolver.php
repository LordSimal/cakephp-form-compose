<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Binding\FieldResolver;
use FormCompose\Binding\ValueResolver;
use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\FieldState;
use FormCompose\Form\FormLifecycle;

/**
 * Infers website controls and creates contact-specific bound fields.
 */
class ContactFieldResolver extends FieldResolver
{
    /**
     * Infer URLs for website fields and retain schema inference for other fields.
     *
     */
    protected function inferType(string $path, FieldConfig $config): string
    {
        return $path === 'website' ? 'url' : parent::inferType($path, $config);
    }

    /**
     * Supply contact-specific default values through the standard source resolver.
     */
    protected function createValueResolver(): ValueResolver
    {
        return new ContactValueResolver($this->binding);
    }

    /**
     * Expose the application's label behavior on every resolved field.
     */
    protected function createBoundField(
        FieldState $state,
        FormLifecycle $lifecycle,
        ?BoundField $parent = null,
    ): BoundField {
        return new ContactField(
            $state,
            $lifecycle,
            $this->rendering,
            $this->createFieldType($state->type),
            $this->createFieldChildren($lifecycle, $parent),
        );
    }
}
