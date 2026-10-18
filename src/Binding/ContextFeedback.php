<?php
declare(strict_types=1);

namespace FormCompose\Binding;

use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use Cake\View\Form\EntityContext;

/** Reads CakePHP validation feedback for scalar, association, and composite field state. */
class ContextFeedback
{
    /** Retain this form's context and original request metadata. */
    public function __construct(protected readonly FormBinding $binding)
    {
    }

    /**
     * Flatten context errors, mapping collection selectors to their association.
     *
     * @return list<string>
     */
    public function messages(string $path): array
    {
        $association = FieldPath::association($path);
        $errors = $this->binding->context->error($association ?? $path);
        if ($association !== null && $this->binding->context instanceof EntityContext) {
            $owner = $this->binding->context->entity(explode('.', $association));
            if ($owner instanceof EntityInterface) {
                $errors = $owner->getError(basename(str_replace('.', '/', $association)));
            }
        }

        return array_map(
            static fn(mixed $message): string => (string)$message,
            array_values(Hash::flatten($errors)),
        );
    }

    /**
     * Read composite-owned messages without duplicating nested child feedback.
     *
     * @return list<string>
     */
    public function directMessages(string $path): array
    {
        return array_map(
            static fn(mixed $message): string => (string)$message,
            array_values(array_filter(
                $this->binding->context->error($path),
                static fn(mixed $message): bool => !is_array($message),
            )),
        );
    }

    /**
     * Read the context's required-validation text for the actual validated property.
     */
    public function requiredMessage(string $path): ?string
    {
        return $this->binding->context->getRequiredMessage(FieldPath::association($path) ?? $path);
    }
}
