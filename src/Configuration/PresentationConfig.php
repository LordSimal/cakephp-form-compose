<?php
declare(strict_types=1);

namespace FormCompose\Configuration;

use FormCompose\Rendering\Attributes;

/**
 * Immutable presentation changes, retaining replacement intent across inheritance.
 * Attribute arrays and template data remain application-owned data bags.
 */
final class PresentationConfig
{
    use PresentationReplacementTrait;
    use PresentationTemplatesTrait;

    /** @var array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} Explicit presentation values. */
    private readonly array $values;

    /** @var array<string, true> Bags explicitly replaced rather than merged. */
    private readonly array $replace;

    /**
     * @param array<string, string>|Unspecified $templates Explicit part elements.
     * @param array<string, mixed>|Unspecified $templateData Application-owned variables.
     * @param array<string, mixed>|Unspecified $attributes Input or wrapper attributes.
     * @param array<string, array<string, mixed>>|Unspecified $partAttributes Per-part attributes.
     */
    public function __construct(
        array|Unspecified $templates = Unspecified::Value,
        string|Unspecified|null $variant = Unspecified::Value,
        array|Unspecified $templateData = Unspecified::Value,
        array|Unspecified $attributes = Unspecified::Value,
        array|Unspecified $partAttributes = Unspecified::Value,
    ) {
        /** @var array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $values */
        $values = array_filter(
            [
                'templates' => $templates,
                'variant' => $variant,
                'templateData' => $templateData,
                'attributes' => $attributes,
                'partAttributes' => $partAttributes,
            ],
            static fn(mixed $value): bool => $value !== Unspecified::Value,
        );
        $this->values = $values;

        $this->replace = [];
        PresentationValidation::validate($this);
    }

    /** @var array<string, mixed> Input or wrapper attributes. */
    public array $attributes {
        get => $this->values['attributes'] ?? [];
    }

    /** @var array<string, array<string, mixed>> Attributes keyed by part. */
    public array $partAttributes {
        get => $this->values['partAttributes'] ?? [];
    }

    /** @var array<string, string> Explicit elements keyed by part. */
    public array $templates {
        get => $this->values['templates'] ?? [];
    }

    /** @var array<string, mixed> Application-owned template variables. */
    public array $templateData {
        get => $this->values['templateData'] ?? [];
    }

    /** Selected variant, or null for ordinary lookup. */
    public ?string $variant {
        get => $this->values['variant'] ?? null;
    }

    /** Merge an immutable presentation layer, honoring explicit bag replacements. */
    public function merge(self $next): self
    {
        return clone($this, [
            'values' => PresentationMerge::values($this->values, $next->values, $next->replace),
            'replace' => $next->replace + $this->replace,
        ]);
    }

    /** @param array<string, mixed> $attributes HTML attributes. */
    public function attributes(array $attributes): self
    {
        $attributes = new Attributes($attributes)->values;

        return $this->merge($this->layer(['attributes' => $attributes], []));
    }

    /** @param array<string, mixed> $attributes HTML attributes for one named part. */
    public function partAttributes(string $part, array $attributes): self
    {
        $attributes = new Attributes($attributes)->values;

        return $this->merge(
            $this->layer(['partAttributes' => [$part => $attributes]], []),
        );
    }

    /** @param array<string, string> $templates Elements keyed by part. */
    public function templates(array $templates): self
    {
        return $this->merge($this->layer(['templates' => $templates], []));
    }

    /** @param array<string, mixed> $data Application-owned variables. */
    public function templateData(array $data): self
    {
        return $this->merge($this->layer(['templateData' => $data], []));
    }

    /** Select or clear a variant independently of explicit elements. */
    public function variant(?string $variant): self
    {
        return $this->merge(new self(variant: $variant));
    }

    /**
     * @param array{attributes?: array<string, mixed>, partAttributes?: array<string, array<string, mixed>>, templates?: array<string, string>, templateData?: array<string, mixed>, variant?: ?string} $values One typed presentation layer.
     * @param array<string, true> $replace Explicit replacement instructions.
     */
    private function layer(array $values, array $replace): self
    {
        $layer = clone(new self(), ['values' => $values, 'replace' => $replace]);
        PresentationValidation::validate($layer);

        return $layer;
    }

    /** Start an immutable fluent configuration; every setting returns a new instance. */
    public static function builder(): self
    {
        return new self();
    }
}
