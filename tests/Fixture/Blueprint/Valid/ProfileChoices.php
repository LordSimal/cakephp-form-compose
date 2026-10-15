<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint\Valid;

/**
 * Lightweight application service used to verify constructor injection.
 */
class ProfileChoices
{
    /**
     * Return choices whose values can be checked in rendered output.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        return ['work' => 'Work <address>', 'home' => 'Home'];
    }
}
