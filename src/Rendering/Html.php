<?php
declare(strict_types=1);

namespace FormCompose\Rendering;

/**
 * Explicitly trusted HTML, distinct from ordinary escaped text.
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
