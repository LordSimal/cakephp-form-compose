<?php
declare(strict_types=1);
/** @var array<int|string, \FormCompose\Blueprint\BlueprintView> $rows */
foreach ($rows as $row) {
    echo $row->render();
}

throw new RuntimeException('Repeated wrapper failed.');
