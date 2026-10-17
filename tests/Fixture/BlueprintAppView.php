<?php
declare(strict_types=1);

namespace FormCompose\Test\Fixture;

use Cake\View\View;

/**
 * Mirrors the documented AppView configuration using controller-supplied view data.
 */
class BlueprintAppView extends View
{
    /**
     * Supply the application factory to the normal helper-loading path.
     */
    public function initialize(): void
    {
        $this->addHelper('Form', [
            'className' => 'FormCompose.Form',
            'blueprintFactory' => $this->get('blueprints'),
        ]);
    }
}
