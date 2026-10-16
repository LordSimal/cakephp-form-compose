<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Binding\FieldOptions;
use FormCompose\Rendering\TemplateOptions;
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
    public static function block(mixed $block): ?string
    {
        return match (true) {
            $block === true => 'postActions',
            $block === null || $block === false => null,
            is_string($block) && trim($block) !== '' => $block,
            default => throw new InvalidArgumentException('The block option must be a nonempty string or a boolean.'),
        };
    }

    /**
     * Accept a confirmation message or an explicitly disabled confirmation.
     */
    public static function confirm(mixed $confirm): ?string
    {
        if ($confirm !== null && $confirm !== false && !is_string($confirm)) {
            throw new InvalidArgumentException('The confirm option must be a string, false, or null.');
        }

        return $confirm === false || $confirm === '' ? null : $confirm;
    }

    /**
     * Preserve trigger presentation while rejecting submission overrides.
     *
     * @param array<string, mixed> $options Trigger attributes and presentation options.
     * @return array<string, mixed>
     */
    public static function trigger(array $options): array
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

        FieldOptions::validate($options);

        return TemplateOptions::normalize($options);
    }
}
