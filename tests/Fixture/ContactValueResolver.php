<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use FormCompose\Binding\ValueResolver;

/**
 * Supplies a default website after the normal request and context lookup.
 */
class ContactValueResolver extends ValueResolver
{
    /**
     * Retain bound values and use a website default only when sources are empty.
     *
     * @param array<string, mixed> $options Field value options.
     */
    protected function fromSources(string $path, array $options): mixed
    {
        return parent::fromSources($path, $options) ?? ($path === 'website' ? 'https://example.test' : null);
    }
}
