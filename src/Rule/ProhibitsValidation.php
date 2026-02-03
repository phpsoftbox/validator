<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function implode;

/**
 * Проверяет, что при наличии поля отсутствуют другие поля.
 */
final class ProhibitsValidation extends AbstractRule
{
    /**
     * Поля, которые должны отсутствовать.
     *
     * @var list<string>
     */
    private array $fields;

    public function __construct(string ...$fields)
    {
        $this->fields = $fields;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!$present) {
            return [];
        }

        foreach ($this->fields as $other) {
            $matches = DataPath::extract($data, $other);
            foreach ($matches as $match) {
                if ($match->present) {
                    return [new ValidationViolation(
                        ValidationEnum::PROHIBITS->value,
                        ['other' => implode(', ', $this->fields)],
                    )];
                }
            }
        }

        return [];
    }

    public function messages(): array
    {
        return [
            ValidationEnum::PROHIBITS->value => 'Поле {field} запрещает наличие полей {other}.',
        ];
    }
}
