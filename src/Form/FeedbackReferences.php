<?php
declare(strict_types=1);

namespace FormCompose\Form;

use FormCompose\Form\Field\BoundField;
use FormCompose\Form\Field\FieldTypeInterface;
use FormCompose\Form\Field\NativeType;
use FormCompose\Rendering\Attributes;
use FormCompose\Rendering\AttributeTokens;
use LogicException;

/**
 * Tracks generated feedback references emitted by successfully rendered inputs.
 *
 * InputRegistry snapshots these expectations with input registrations. Closing
 * verifies that generated help/error IDs have been rendered or explicitly
 * registered. Application-owned references to unrelated DOM elements remain
 * the application's responsibility; attribute inspection records nothing.
 */
class FeedbackReferences
{
    /** @var array<string, true> Generated feedback IDs referenced by rendered inputs. */
    protected array $expected = [];

    /**
     * Record only enabled generated feedback IDs retained in the final input bag.
     *
     * @param list<string> $ids Additional emitted native choice identities.
     */
    public function record(
        BoundField $field,
        Attributes $attributes,
        ?FieldTypeInterface $type = null,
        array $ids = [],
    ): void {
        if ($type instanceof NativeType && $field->state->grouped() && $ids === []) {
            return;
        }

        $matches = [];
        preg_match_all('/\S+/', AttributeTokens::normalize($attributes->values['aria-describedby'] ?? ''), $matches);
        $references = $matches[0];
        $parts = ['help' => $field->state->hasHelp(), 'errors' => $field->state->hasErrors()];
        foreach (array_keys(array_filter($parts)) as $part) {
            $bag = $field->attributes($part);
            $id = (string)($bag->values['id'] ?? '');
            if ($bag->has('id') && in_array($id, $references, true)) {
                $this->expected[$id] = true;
            }
        }

        if ($field->parent instanceof BoundField) {
            $this->record($field->parent, $attributes);
        }
    }

    /**
     * Snapshot expectations without registering fields or rendering markup.
     *
     * @return array<string, true>
     */
    public function expectations(): array
    {
        return $this->expected;
    }

    /**
     * Restore expectations when an enclosing render transaction fails.
     *
     * @param array<string, true> $expected Previously recorded feedback IDs.
     */
    public function restore(array $expected): void
    {
        $this->expected = $expected;
    }

    /**
     * Require referenced feedback elements before finalizing form protection.
     *
     * @param array<string, true> $rendered Rendered or explicitly registered DOM IDs.
     * @throws \LogicException When generated references have no registered target.
     */
    public function assertResolved(array $rendered): void
    {
        $missing = array_diff_key($this->expected, $rendered);
        if ($missing !== []) {
            throw new LogicException(
                'Render or register referenced feedback before closing the form: '
                    . implode(', ', array_keys($missing)),
            );
        }
    }
}
