<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Field;

use FormCompose\Field\Choice;
use FormCompose\Field\NativeErrorTarget;
use FormCompose\Rendering\Attributes;
use FormCompose\Test\FormTestCase;

/**
 * Covers target discovery for custom prepared choice trees without emitted identities.
 */
class NativeErrorTargetTest extends FormTestCase
{
    public function testChoicesWithoutIdsAreSkippedWhenFindingAFocusTarget(): void
    {
        $state = $this->helper()->bind()->field('status', ['type' => 'radio'])->state;
        $missing = new Choice('group', 'No target', new Attributes(), [
            new Choice('item', 'No identity', new Attributes(['value' => 'a'])),
        ]);
        $enabled = new Choice('item', 'Enabled', new Attributes(['id' => 'status-b', 'value' => 'b']));
        static::assertNull(NativeErrorTarget::resolve($state, [$missing]));
        static::assertSame('status-b', NativeErrorTarget::resolve($state, [$missing, $enabled]));
    }
}
