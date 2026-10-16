<?php
declare(strict_types=1);

namespace FormCompose\Field;

/**
 * Selects native summary targets from the already prepared choice tree.
 *
 * Hidden controls have no target; grouped choices link to their first enabled input.
 */
final class NativeErrorTarget
{
    /**
     * Resolve a target without rendering or consuming the original choice iterable.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared native choices.
     */
    public static function resolve(FieldState $state, array $choices): ?string
    {
        return match ($state->type) {
            'hidden' => null,
            'radio', 'multicheckbox' => self::firstTarget($choices),
            default => $state->id,
        };
    }

    /**
     * Find the first enabled choice input, including within nested fieldsets.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared choices.
     */
    private static function firstTarget(array $choices): ?string
    {
        foreach ($choices as $choice) {
            if ($choice->attributes->has('disabled')) {
                continue;
            }

            $target = match (true) {
                $choice->kind === 'group' => self::firstTarget($choice->children),
                $choice->attributes->has('id') => (string)$choice->attributes->values['id'],
                default => null,
            };
            if ($target !== null) {
                return $target;
            }
        }

        return null;
    }
}
