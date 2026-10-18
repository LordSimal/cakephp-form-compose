<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\ActionConfig;
use FormCompose\Configuration\ButtonConfig;

/** Authors native button state alongside the protected action's submission and presentation. */
class PostButtonBuilder extends ActionBuilder
{
    use ButtonStateMethodsTrait;

    /** Read native button state from its configuration domain. */
    protected function buttonConfig(): ActionConfig
    {
        return $this->config();
    }

    /** Store native button state in its configuration domain. */
    protected function updateButtonConfig(ButtonConfig|ActionConfig $config): void
    {
        assert($config instanceof ActionConfig, 'Button updates must preserve the configuration domain.');
        $this->configuration = $config;
    }
}
