<?php
declare(strict_types=1);

namespace FormCompose\Form\Field;

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
    public function __construct(public bool $submittable, public array $ids, public ?string $target = null)
    {
    }

    /**
     * Inspect prepared choices, including nested groups, without executing templates.
     *
     * @param list<\FormCompose\Form\Field\Choice> $choices Prepared choice tree.
     */
    public static function from(FieldState $state, array $choices, ?Attributes $fallback): self
    {
        $ids = [];
        [$hasEnabled, $target] = self::inspectChoices($choices, $state->type, $ids);
        $target = match ($state->type) {
            'hidden' => null,
            'radio', 'multicheckbox' => $target,
            default => $state->id,
        };

        return new self(
            $state->type !== 'select' || $fallback instanceof Attributes || $hasEnabled,
            $ids,
            $target,
        );
    }

    /**
     * Collect emitted IDs, select submission eligibility, and the first choice target.
     *
     * @param list<\FormCompose\Form\Field\Choice> $choices Prepared choice tree.
     * @param list<string> $ids
     * @return array{bool, ?string}
     */
    private static function inspectChoices(array $choices, string $type, array &$ids): array
    {
        $enabled = false;
        $target = null;
        foreach ($choices as $choice) {
            $bags = $type === 'select'
                ? [$choice->attributes]
                : [$choice->attributes, ...array_values($choice->presentation)];
            foreach ($bags as $bag) {
                if (!$bag->has('id')) {
                    continue;
                }

                $ids[] = (string)$bag->values['id'];
            }

            if ($choice->kind === 'group') {
                [$childEnabled, $childTarget] = self::inspectChoices($choice->children, $type, $ids);
                if (!$choice->attributes->has('disabled') && $childEnabled) {
                    $enabled = true;
                    $target ??= $childTarget;
                }
            } elseif (!$choice->attributes->has('disabled')) {
                $enabled = true;
                $target ??= $choice->attributes->has('id') ? (string)$choice->attributes->values['id'] : null;
            }
        }

        return [$enabled, $target];
    }
}
