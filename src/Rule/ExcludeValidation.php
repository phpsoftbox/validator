<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;

use function in_array;

/**
 * Управляющее правило для исключения поля из валидации.
 */
final class ExcludeValidation extends AbstractControlRule
{
    /**
     * Набор условий, при которых поле исключается.
     *
     * @var list<array{rule: string, fn: callable}>
     */
    private array $conditions = [];

    /**
     * Исключить поле из валидации без условий.
     */
    public function exclude(): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE->value,
            fn (array $data, mixed $context = null, string $field = ''): bool => true,
        );
    }

    /**
     * Исключить поле при выполнении callback.
     * Callback получает context, если он передан в Validator, иначе массив данных.
     */
    public function excludeIf(callable $callback): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_IF->value,
            function (array $data, mixed $context, string $field) use ($callback): bool {
                if ($context !== null) {
                    return (bool) $callback($context);
                }

                return (bool) $callback($data);
            },
        );
    }

    /**
     * Исключить поле, если связанное поле не равно любому из значений.
     */
    public function excludeUnless(string $field, mixed ...$values): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_UNLESS->value,
            function (array $data, mixed $context, string $currentField) use ($field, $values): bool {
                $matches    = DataPath::extract($data, $field);
                $hasPresent = false;
                foreach ($matches as $match) {
                    if (!$match->present) {
                        continue;
                    }
                    $hasPresent = true;
                    if (in_array($match->value, $values, true)) {
                        return false;
                    }
                }

                if ($hasPresent) {
                    return true;
                }

                return !in_array(null, $values, true);
            },
        );
    }

    /**
     * Исключить поле при наличии хотя бы одного из связанных полей.
     */
    public function excludeWith(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_WITH->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if ($this->isPresent($data, $field)) {
                        return true;
                    }
                }

                return false;
            },
        );
    }

    /**
     * Исключить поле при наличии всех связанных полей.
     */
    public function excludeWithAll(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_WITH_ALL->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if (!$this->isPresent($data, $field)) {
                        return false;
                    }
                }

                return $fields !== [];
            },
        );
    }

    /**
     * Исключить поле, если отсутствует хотя бы одно из связанных полей.
     */
    public function excludeWithout(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_WITHOUT->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if (!$this->isPresent($data, $field)) {
                        return true;
                    }
                }

                return false;
            },
        );
    }

    /**
     * Исключить поле, если отсутствуют все связанные поля.
     */
    public function excludeWithoutAll(string ...$fields): static
    {
        return $this->condition(
            ValidationEnum::EXCLUDE_WITHOUT_ALL->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if ($this->isPresent($data, $field)) {
                        return false;
                    }
                }

                return $fields !== [];
            },
        );
    }

    /**
     * Определяет, нужно ли исключить поле из валидации.
     */
    public function shouldExclude(array $data, mixed $context, string $field): bool
    {
        if ($this->conditions === []) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $fn = $condition['fn'];
            if ($fn($data, $context, $field) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Валидатор не выполняет проверок, так как правило управляющее.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        return [];
    }

    /**
     * Сообщения для исключения не используются.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Добавить условие исключения.
     */
    private function condition(string $rule, callable $fn): static
    {
        $this->conditions[] = ['rule' => $rule, 'fn' => $fn];

        return $this;
    }

    /**
     * Проверить, что поле присутствует в данных.
     */
    private function isPresent(array $data, string $field): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if ($match->present) {
                return true;
            }
        }

        return false;
    }
}
