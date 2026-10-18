<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Distinguishes an omitted setting from an explicitly supplied null value. */
enum Unspecified
{
    case Value;
}
