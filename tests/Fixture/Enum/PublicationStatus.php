<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

/**
 * Exercises case-name labels and empty-string backing values.
 */
enum PublicationStatus: string
{
    case Unspecified = '';
    case InReview = 'in-review';
    case Published = 'published';
}
