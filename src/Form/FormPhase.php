<?php
declare(strict_types=1);

namespace FormCompose\Form;

/**
 * Names the stages enforced by FormLifecycle for one form session.
 *
 * Pending means the form has not opened. Open accepts input registrations.
 * Finalizing means closing tokens are prepared and input collection is frozen.
 * Closed means the closing markup rendered successfully.
 */
enum FormPhase
{
    case Pending;
    case Open;
    case Finalizing;
    case Closed;
}
