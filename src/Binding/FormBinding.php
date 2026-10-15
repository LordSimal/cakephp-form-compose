<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;
use FormCompose\FormDefinition;

/**
 * Request and context configuration shared by fields of one form.
 */
final readonly class FormBinding
{
    /**
     * Capture binding inputs independently of rendering state.
     *
     * @param array<string, mixed> $options Form creation options and valueSources.
     */
    public function __construct(
        public ContextInterface $context,
        public ServerRequest $request,
        public string $id,
        public array $options = [],
        public ?FormDefinition $definition = null,
    ) {
    }
}
