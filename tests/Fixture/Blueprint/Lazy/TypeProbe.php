<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Lazy;

/**
 * Counts construction without coupling assertions to factory implementation details.
 */
class TypeProbe
{
    public int $constructions = 0;

    /**
     * Read the count after application callbacks have run.
     */
    public function count(): int
    {
        return $this->constructions;
    }
}
