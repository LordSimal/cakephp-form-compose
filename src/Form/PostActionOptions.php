<?php
declare(strict_types=1);

namespace FormCompose\Form;

use InvalidArgumentException;

/**
 * Normalizes action placement, confirmation, and trigger presentation options.
 *
 * PostAction uses these checks before creating a session. Browser attributes may
 * customize presentation but cannot redirect the protected action's submission.
 */
final class PostActionOptions
{
    /**
     * Select inline output or a CakePHP view block.
     */
    public static function block(?string $block): ?string
    {
        if ($block !== null && trim($block) === '') {
            throw new InvalidArgumentException('The block name must be a nonempty string.');
        }

        return $block;
    }

    /** Normalize an empty confirmation message to disabled confirmation. */
    public static function confirm(?string $confirm): ?string
    {
        return $confirm === '' ? null : $confirm;
    }

    /**
     * Preserve trigger presentation while rejecting submission overrides.
     *
     * @param array<string, mixed> $options Trigger attributes and presentation options.
     */
    public static function trigger(array $options): void
    {
        if (isset($options['type']) && $options['type'] !== 'submit') {
            throw new InvalidArgumentException('Post action buttons must use type submit.');
        }

        foreach ([
            'href',
            'formaction',
            'formmethod',
            'formenctype',
            'formnovalidate',
            'data-form-compose-action',
        ] as $key) {
            if (array_key_exists($key, $options)) {
                throw new InvalidArgumentException('The ' . $key . ' action attribute is managed by FormCompose.');
            }
        }
    }
}
