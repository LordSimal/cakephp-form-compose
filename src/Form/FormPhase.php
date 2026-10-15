<?php
declare(strict_types=1);

namespace FormCompose\Form;

/**
 * Explicit states separating preparation, editable input collection, and finalized output.
 */
enum FormPhase
{
    case Pending;
    case Open;
    case Finalizing;
    case Closed;
}
