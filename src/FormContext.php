<?php
declare(strict_types=1);

namespace FormCompose;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;

/**
 * Supplies one binding's data metadata and request to blueprint configuration.
 *
 * The context can inspect values with context->val('field') and request attributes
 * without access to rendering or submission-registration services. It is never cached.
 */
final readonly class FormContext
{
    /**
     * Capture the same context and request used by the resulting form session.
     */
    public function __construct(public ContextInterface $context, public ServerRequest $request)
    {
    }
}
