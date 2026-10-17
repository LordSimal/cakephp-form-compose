<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

use function Cake\I18n\__dx;

/**
 * Stores message metadata until a field resolves its label or help text.
 *
 * Use new Translated('Article title') in a Field attribute instead of a closure
 * for static messages. FieldText translates it through CakePHP in the current
 * locale after option merging. This immutable value never contains trusted HTML
 * and does not translate during construction or cache locale-specific results.
 */
final readonly class Translated
{
    /**
     * Describe a CakePHP message, optional formatting arguments, domain, and context.
     *
     * @param array<array-key, mixed> $arguments CakePHP message-format arguments.
     */
    public function __construct(
        public string $message,
        public array $arguments = [],
        public string $domain = 'default',
        public string $context = '',
    ) {
    }

    /**
     * Translate using CakePHP's active locale; field rendering escapes the result.
     */
    public function translate(): string
    {
        return __dx($this->domain, $this->context, $this->message, $this->arguments);
    }
}
