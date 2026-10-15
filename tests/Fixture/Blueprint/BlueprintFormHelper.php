<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture\Blueprint;

use FormCompose\Blueprint\BlueprintFactory;
use FormCompose\View\Helper\FormHelper;

/**
 * Tests that helper subclass factories are honored by the public bind() method.
 */
class BlueprintFormHelper extends FormHelper
{
    /**
     * Select the custom factory without requiring helper configuration.
     */
    protected function createBlueprintFactory(): BlueprintFactory
    {
        return new CustomBlueprintFactory();
    }
}
