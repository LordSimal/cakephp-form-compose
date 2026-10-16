<?php
declare(strict_types=1);

namespace FormCompose\Form;

/**
 * Retains feedback from successful render transactions for one form session.
 *
 * InputRegistry snapshots and restores this registry with IDs and submissions.
 * Summary rendering only reads it; inspection of unresolved fields never records entries.
 */
class FeedbackRegistry
{
    /** @var array<string, \FormCompose\Form\FeedbackEntry> Feedback in successful render order. */
    protected array $entries = [];

    /**
     * Record eligible feedback once per rendered presentation identity.
     */
    public function record(string $identity, ?FeedbackEntry $entry): void
    {
        if ($entry instanceof FeedbackEntry) {
            $this->entries[$identity] = $entry;
        }
    }

    /**
     * Read the immutable entries without changing rendering or protection state.
     *
     * @return array<string, \FormCompose\Form\FeedbackEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * Restore the snapshot after an enclosing input, definition, or wrapper fails.
     *
     * @param array<string, \FormCompose\Form\FeedbackEntry> $entries Earlier successful feedback.
     */
    public function restore(array $entries): void
    {
        $this->entries = $entries;
    }
}
