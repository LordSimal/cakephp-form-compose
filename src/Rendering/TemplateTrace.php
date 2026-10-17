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
     * Put diagnostic boundaries on separate lines while keeping empty parts empty.
     *
     * @param non-negative-int $depth Render nesting level; only comments are indented.
     */
    public function wrap(string $html, RenderNode $node, int $depth = 0): string
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

        if ($node->scope === 'form' && $this->part === 'hidden') {
            /** @var mixed $attributes */
            $attributes = $node->variables['attributes'] ?? null;
            /** @var mixed $name */
            $name = $attributes instanceof Attributes ? $attributes->values['name'] ?? null : null;
            if (is_string($name)) {
                $lines[] = 'field: ' . $name;
                $purpose = match ($name) {
                    '_csrfToken' => 'CSRF protection',
                    '_method' => 'HTTP method override',
                    '_Token[fields]' => 'Form protection signature',
                    '_Token[unlocked]' => 'Form protection unlocked fields',
                    '_Token[debug]' => 'Form protection diagnostics',
                    default => 'Internal form input',
                };
                $lines[] = 'purpose: ' . $purpose;
            }
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

        $indent = str_repeat('  ', $depth);
        $separator = str_ends_with($html, "\n") ? '' : "\n";

        return (
            "\n"
                . $this->comment($lines, $indent)
                . $html
                . $separator
                . $this->comment(['FormCompose END part=' . $this->part], $indent)
        );
    }

    /**
     * Encode metadata as inert comment text, keeping identifiers on one line.
     *
     * @param list<string> $lines Diagnostic lines without values or template variables.
     */
    private function comment(array $lines, string $indent): string
    {
        $lines = array_map(
            static fn(string $line): string => str_replace(
                '--',
                '&#45;&#45;',
                Html::text(str_replace(["\r", "\n"], ['\\r', '\\n'], $line)),
            ),
            $lines,
        );

        if (count($lines) === 1) {
            return $indent . '<!-- ' . $lines[0] . " -->\n";
        }

        return $indent . '<!-- ' . implode("\n" . $indent, $lines) . "\n" . $indent . "-->\n";
    }
}
