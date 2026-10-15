<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Http\ServerRequest;
use Cake\View\Form\ContextInterface;

/**
 * Holds the data sources and configuration shared by one form session.
 *
 * The CakePHP context supplies schema, entity values, and validation errors; the
 * request supplies submitted data and security metadata. FieldResolver and
 * FormConstruction read this object to prepare fields and form metadata.
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
    ) {
    }
}
