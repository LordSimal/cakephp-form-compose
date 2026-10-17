<?php
declare(strict_types=1);

namespace FormCompose\Test\TestCase\Form\Field;

use FormCompose\Form\Field\Choice;
use FormCompose\Form\Field\NativeInput;
use FormCompose\Rendering\Attributes;
use FormCompose\Test\FormTestCase;

/**
 * Covers target discovery for custom prepared choice trees without emitted identities.
 */
class NativeInputTest extends FormTestCase
{
    public function testChoicesWithoutIdsAreSkippedWhenFindingAFocusTarget(): void
    {
        $state = $this->helper()->bind()->field('status', ['type' => 'radio'])->state;
        $missing = new Choice('group', 'No target', new Attributes(), [
            new Choice('item', 'No identity', new Attributes(['value' => 'a'])),
        ]);
        $enabled = new Choice('item', 'Enabled', new Attributes(['id' => 'status-b', 'value' => 'b']));
        static::assertNull(NativeInput::from($state, [$missing], null)->target);
        static::assertSame('status-b', NativeInput::from($state, [$missing, $enabled], null)->target);
    }
}
