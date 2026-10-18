<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Builder\PartBuilder;
use FormCompose\Form\Field\BoundField;

/**
 * Adds contact metadata to labels rendered by both complete and composed controls.
 */
class ContactField extends BoundField
{
    /**
     * Add application label metadata while preserving normal label presentation.
     *
     */
    public function label(): PartBuilder
    {
        return parent::label()->attributes(['data-contact-label' => true]);
    }
}
