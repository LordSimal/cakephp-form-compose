<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

/**
 * Backed enum used to verify native form value normalization.
 */
enum Status: string
{
    case Published = 'published';
}
