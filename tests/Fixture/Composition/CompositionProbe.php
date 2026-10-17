<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Composition;

use FormCompose\Blueprint\FormContext;

/**
 * Records shared DI and hook ordering while each blueprint instance remains isolated.
 */
class CompositionProbe
{
    /** @var list<array{name: string, context: \FormCompose\Blueprint\FormContext}> */
    public array $calls = [];

    /**
     * Retain the real context for public metadata assertions.
     */
    public function record(string $name, FormContext $context): void
    {
        $this->calls[] = ['name' => $name, 'context' => $context];
    }
}
