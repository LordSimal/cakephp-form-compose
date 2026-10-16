<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * Exercises integer zero and application-owned labels containing markup.
 */
enum Priority: int implements EnumLabelInterface
{
    case Normal = 0;
    case Urgent = 2;

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Standard <priority>',
            self::Urgent => 'Act now',
        };
    }
}
