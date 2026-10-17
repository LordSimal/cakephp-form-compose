<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Widget;

use FormCompose\Attribute\AsFieldType;
use FormCompose\Test\Fixture\Blueprint\Lazy\TypeProbe;

/**
 * Exercises composite discovery metadata without eager container access.
 */
#[AsFieldType('dependency-range')]
class DependencyRangeType extends DateRangeType
{
    /**
     * Record real construction through the injected application service.
     */
    public function __construct(TypeProbe $probe)
    {
        $probe->constructions++;
    }
}
