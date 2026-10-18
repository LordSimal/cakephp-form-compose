<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Configuration\FieldConfig;
use FormCompose\Form\Field\BoundField;
use FormCompose\Form\FormSession;
use InvalidArgumentException;

/** Keeps scoped control chains usable when a blueprint deliberately removed that field. */
class OmittedControlBuilder extends ControlBuilder
{
    /** Preserve the normal fluent field API without resolving an omitted field. */
    public function __construct(FormSession $owner, FieldConfig|FieldConfigBuilder $config = new FieldConfig())
    {
        parent::__construct(
            static fn(FieldConfig $settings): BoundField => throw new InvalidArgumentException(
                'Cannot resolve an omitted blueprint field.',
            ),
            $config,
            $owner,
        );
    }

    /** An omitted control emits no markup and registers no inputs. */
    public function render(): string
    {
        return '';
    }
}
