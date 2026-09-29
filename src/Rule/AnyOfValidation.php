<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

/**
 * Проверяет, что значение проходит хотя бы одно из переданных правил.
 */
final class AnyOfValidation extends AbstractRule
{
    /**
     * Набор правил, одно из которых должно пройти.
     *
     * @var list<ValidationRuleInterface>
     */
    private array $rules;

    public function __construct(ValidationRuleInterface ...$rules)
    {
        $this->rules = $rules;
    }

    /**
     * Вложенные правила (используются валидатором для выполнения через общий конвейер).
     *
     * @return list<ValidationRuleInterface>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Прямой вызов проверяет только обычные правила; specification-правила и runtime-состояние
     * (route()/context()) поддерживаются при выполнении через Validator.
     */
    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        foreach ($this->rules as $rule) {
            if ($rule->validate($value, $field, $present, $data) === []) {
                return [];
            }
        }

        return [new ValidationViolation(ValidationEnum::ANY_OF->value)];
    }

    public function messages(): array
    {
        return [
            ValidationEnum::ANY_OF->value => 'Поле {field} не соответствует ни одному из допустимых правил.',
        ];
    }
}
