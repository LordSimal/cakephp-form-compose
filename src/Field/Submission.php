<?php
declare(strict_types=1);

namespace FormCompose\Field;

/**
 * Describes one input name emitted by a field type for form protection.
 *
 * FieldTypeInterface::submissions() returns these records. A normal field protects
 * its presence; a non-null value also locks its contents, as needed for hidden
 * inputs. Setting locked to false allows that name to vary or be omitted.
 *
 * ```php
 * new Submission('period[start]');
 * new Submission('record_id', value: '42');
 * ```
 */
final readonly class Submission
{
    /**
     * Describe a normal field, locked hidden value, or explicitly unlocked integration field.
     */
    public function __construct(public string $name, public bool $locked = true, public ?string $value = null)
    {
    }
}
