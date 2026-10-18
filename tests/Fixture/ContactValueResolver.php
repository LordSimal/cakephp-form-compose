<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Binding\ValueResolver;
use FormCompose\Configuration\FieldConfig;

/**
 * Supplies a default website after the normal request and context lookup.
 */
class ContactValueResolver extends ValueResolver
{
    /**
     * Retain bound values and use a website default only when sources are empty.
     *
     */
    protected function fromSources(string $path, FieldConfig $config): mixed
    {
        return parent::fromSources($path, $config) ?? ($path === 'website' ? 'https://example.test' : null);
    }
}
