<?php
declare(strict_types=1);

namespace FormCompose\Form;

use Cake\View\View;
use InvalidArgumentException;
use LogicException;

/**
 * Allocates form identities across services attached to one Cake view.
 *
 * FormHelper injects its view into this internal service. Allocation state is
 * stored as plain bookkeeping in a reserved view variable, sharing the view's
 * lifetime without registering helpers or using process-global state.
 *
 * @internal
 */
class FormIds
{
    /**
     * Use the owning view as the lifetime of the shared identity bookkeeping.
     */
    public function __construct(protected readonly View $view)
    {
    }

    /**
     * Reserve an explicit identity or allocate the next available automatic one.
     *
     * @throws \InvalidArgumentException For empty IDs or IDs containing whitespace.
     * @throws \LogicException When an explicit identity is already reserved.
     */
    public function allocate(?string $id = null): string
    {
        /** @var array{sequence: int, reserved: array<string, true>} $state */
        $state = $this->view->get('__formComposeIds', ['sequence' => 0, 'reserved' => []]);
        if ($id === null) {
            do {
                $id = 'form-compose-' . ++$state['sequence'];
            } while (isset($state['reserved'][$id]));
        }

        if ($id === '' || preg_match('/\s/u', $id)) {
            throw new InvalidArgumentException('Form IDs must be nonempty and contain no whitespace.');
        }

        if (isset($state['reserved'][$id])) {
            throw new LogicException('A form with this ID has already been bound in this view: ' . $id);
        }

        $state['reserved'][$id] = true;
        $this->view->set('__formComposeIds', $state);

        return $id;
    }
}
