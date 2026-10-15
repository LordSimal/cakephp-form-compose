<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Marks developer-controlled HTML that should be rendered as trusted markup.
 *
 * Labels, help text, and button content normally pass through text() for escaping.
 * Wrapping content in Html preserves it verbatim, so only trusted markup should be
 * wrapped; this class does not sanitize HTML.
 *
 * ```php
 * $form->submit(new Html('<strong>Save</strong>'));
 * Html::text('Save & continue'); // Returns 'Save &amp; continue'.
 * ```
 */
final readonly class Html
{
    /**
     * Wrap developer-controlled markup.
     */
    public function __construct(public string $value)
    {
    }

    /**
     * Escape ordinary text while preserving explicitly trusted markup.
     */
    public static function text(string|self $text): string
    {
        return $text instanceof self ? $text->value : htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
