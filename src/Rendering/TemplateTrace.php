<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

use FormCompose\Field\BoundField;

/**
 * Describes one successful PHP element lookup for optional HTML comment diagnostics.
 *
 * PhpRenderer creates this local record after rendering succeeds. Only template
 * identifiers and field paths are exposed; values and other variables stay private.
 *
 * @internal Rendering diagnostic metadata, not an application configuration API.
 */
final readonly class TemplateTrace
{
    /**
     * Capture the selected element and the candidates actually attempted.
     *
     * @param list<string> $lookup Ordered MISS/MATCH entries; explicit overrides have no lookup.
     */
    public function __construct(
        public string $part,
        public string $element,
        public string $source,
        public array $lookup = [],
    ) {
    }

    /**
     * Wrap successful markup without adding whitespace or making empty parts nonempty.
     */
    public function wrap(string $html, RenderNode $node): string
    {
        if ($html === '') {
            return $html;
        }

        $lines = ['FormCompose BEGIN', 'scope: ' . $node->scope, 'part: ' . $this->part, 'type: ' . $node->type];
        /** @var mixed $field */
        $field = $node->variables['field'] ?? null;
        if ($field instanceof BoundField) {
            $lines[] = 'field: ' . $field->state->path;
        }

        /** @var mixed $variant */
        $variant = $node->options['variant'] ?? null;
        if (is_string($variant)) {
            $lines[] = 'variant: ' . $variant;
        }

        $lines[] = 'source: ' . $this->source;
        $lines[] = 'element: ' . $this->element;
        if ($this->lookup !== []) {
            $lines[] = 'lookup:';
            foreach ($this->lookup as $attempt) {
                $lines[] = '  ' . $attempt;
            }
        }

        return $this->comment($lines) . $html . $this->comment(['FormCompose END part=' . $this->part]);
    }

    /**
     * Encode metadata as inert comment text, keeping identifiers on one line.
     *
     * @param list<string> $lines Diagnostic lines without values or template variables.
     */
    private function comment(array $lines): string
    {
        $lines = array_map(
            static fn(string $line): string => str_replace(
                '--',
                '&#45;&#45;',
                Html::text(str_replace(["\r", "\n"], ['\\r', '\\n'], $line)),
            ),
            $lines,
        );

        return '<!-- ' . implode("\n", $lines) . ' -->';
    }
}
