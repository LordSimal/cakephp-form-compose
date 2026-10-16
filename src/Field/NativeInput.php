<?php
declare(strict_types=1);

namespace FormCompose\Field;

use FormCompose\Rendering\Attributes;

/**
 * Retains native render metadata for registration without rereading choice iterators.
 *
 * NativeType creates this after rendering. Empty/all-disabled selects with no
 * fallback submit nothing; choice IDs join the main ID in the session registry.
 */
final readonly class NativeInput
{
    /**
     * Capture the successful-control decision and additional choice DOM IDs.
     *
     * @param list<string> $ids Emitted choice IDs beyond the bound field's main ID.
     */
    public function __construct(public bool $submittable, public array $ids)
    {
    }

    /**
     * Inspect prepared choices, including nested groups, without executing templates.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared choice tree.
     */
    public static function from(FieldState $state, array $choices, ?Attributes $fallback): self
    {
        $ids = [];
        foreach (self::flatten($choices) as $choice) {
            $bags = $state->type === 'select'
                ? [$choice->attributes]
                : [$choice->attributes, ...array_values($choice->presentation)];
            foreach ($bags as $bag) {
                if (!$bag->has('id')) {
                    continue;
                }

                $ids[] = (string)$bag->values['id'];
            }
        }

        return new self(
            $state->type !== 'select' || $fallback instanceof Attributes || self::hasEnabledChoice($choices),
            $ids,
        );
    }

    /**
     * Account for disabled options and optgroups when deciding if a select can submit.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared choice tree.
     */
    private static function hasEnabledChoice(array $choices): bool
    {
        foreach ($choices as $choice) {
            if (
                !$choice->attributes->has('disabled')
                && (
                    $choice->kind !== 'group'
                    || self::hasEnabledChoice($choice->children)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flatten choices and their group wrappers, retaining their generated identities.
     *
     * @param list<\FormCompose\Field\Choice> $choices Prepared choice tree.
     * @return list<\FormCompose\Field\Choice>
     */
    private static function flatten(array $choices): array
    {
        $flattened = [];
        foreach ($choices as $choice) {
            array_push($flattened, $choice, ...self::flatten($choice->children));
        }

        return $flattened;
    }
}
