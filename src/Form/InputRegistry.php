<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Closure;
use FormCompose\Security\FormProtection;
use LogicException;
use Throwable;

/**
 * Tracks rendered input identities and atomic definition registrations.
 *
 * FormLifecycle enforces form phases and multipart requirements. Definitions stage
 * successful inputs here until their layout succeeds; failed layouts can be retried
 * without retaining IDs or partial protection data. Inject through FormLifecycle.
 */
class InputRegistry
{
    /** @var array<string, true> Rendered identities, including staged inputs. */
    protected array $rendered = [];

    /** @var list<\FormCompose\Field\Submission> Inputs staged until the outermost definition succeeds. */
    protected array $pending = [];

    protected int $depth = 0;

    /**
     * Share the owning lifecycle's protection service.
     */
    public function __construct(protected readonly FormProtection $protection)
    {
    }

    /**
     * Reject duplicate input identities, including within nested layouts.
     */
    public function assertAvailable(string $id): void
    {
        if (isset($this->rendered[$id])) {
            throw new LogicException('An input with this ID has already been rendered: ' . $id);
        }
    }

    /**
     * Record actual submissions after a field renders successfully.
     *
     * @param list<\FormCompose\Field\Submission> $submissions Actual submitted inputs.
     */
    public function register(string $id, array $submissions): void
    {
        if ($this->depth > 0) {
            array_push($this->pending, ...$submissions);
        } else {
            $this->protection->register($submissions);
        }

        $this->rendered[$id] = true;
    }

    /**
     * Commit inputs only after the outermost layout succeeds, or restore the snapshot.
     *
     * @param \Closure(): string $render Definition rendering operation.
     */
    public function render(Closure $render): string
    {
        $rendered = $this->rendered;
        $pending = $this->pending;
        $this->depth++;
        try {
            $html = $render();
        } catch (Throwable $throwable) {
            $this->rendered = $rendered;
            $this->pending = $pending;
            throw $throwable;
        } finally {
            $this->depth--;
        }

        if ($this->depth === 0) {
            $this->protection->register($this->pending);
            $this->pending = [];
        }

        return $html;
    }
}
