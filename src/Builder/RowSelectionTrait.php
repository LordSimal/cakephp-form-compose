<?php
declare(strict_types=1);

namespace FormCompose\Builder;

use FormCompose\Blueprint\RepeatedDefinition;

/** Selects row keys only while a repeated blueprint can still be compiled. */
trait RowSelectionTrait
{
    /** @var list<int|string>|null Null infers rows; an empty list selects no rows. */
    protected ?array $selectedKeys = null;

    /** @param list<int|string>|null $keys Select original collection keys before compiling row hooks. */
    public function keys(?array $keys): static
    {
        if ($keys !== null) {
            RepeatedDefinition::validateKeys($keys);
        }

        $this->selectedKeys = $keys;

        return $this;
    }
}
