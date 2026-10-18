<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

/** Immutable form configuration captured before binding or rendering. */
final class FormConfig extends Configuration
{
    use FormSubmissionSettingsTrait;
    use FormBindingSettingsTrait;
    use FormLayoutSettingsTrait;

    /** Initialize common named settings without losing explicit null values. */
    public function __construct(
        array|string|false|Unspecified|null $url = Unspecified::Value,
        string|Unspecified|null $method = Unspecified::Value,
        string|Unspecified|null $id = Unspecified::Value,
    ) {
        parent::__construct(array_filter(
            ['url' => $url, 'method' => $method, 'id' => $id],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        ));
    }

    /** Complete form presentation selects its layout part. */
    protected function primaryPart(): string
    {
        return 'layout';
    }

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
