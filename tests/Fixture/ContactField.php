<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Field\BoundField;

/**
 * Adds contact metadata to labels rendered by both complete and composed controls.
 */
class ContactField extends BoundField
{
    /**
     * Add application label metadata while preserving normal label presentation.
     *
     * @param array<string, mixed> $attributes Label attributes.
     */
    public function label(array $attributes = []): string
    {
        return parent::label(['data-contact-label' => true] + $attributes);
    }
}
