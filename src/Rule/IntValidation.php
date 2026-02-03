<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use InvalidArgumentException;
use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function ltrim;
use function preg_match;
use function rtrim;
use function sprintf;
use function str_replace;
use function strlen;
use function strpbrk;
use function trim;

/**
 * Проверяет целые числа и связанные ограничения.
 */
final class IntValidation extends AbstractRule
{
    /**
     * Минимальное значение.
     */
    private ?int $min = null;
    /**
     * Максимальное значение.
     */
    private ?int $max = null;
    /**
     * Минимум в диапазоне between().
     */
    private ?int $betweenMin = null;
    /**
     * Максимум в диапазоне between().
     */
    private ?int $betweenMax = null;
    /**
     * Точное значение (size()).
     */
    private ?int $size = null;
    /**
     * Точное количество цифр.
     */
    private ?int $digits = null;
    /**
     * Минимальное количество цифр.
     */
    private ?int $minDigits = null;
    /**
     * Максимальное количество цифр.
     */
    private ?int $maxDigits = null;
    /**
     * Диапазон количества цифр.
     *
     * @var array{min: int, max: int}|null
     */
    private ?array $digitsBetween = null;
    /**
     * Требовать кратность числу.
     */
    private ?int $multipleOf = null;
    /**
     * Допустимые значения.
     *
     * @var array<int, int>|null
     */
    private ?array $in = null;
    /**
     * Поле для сравнения на равенство.
     */
    private ?string $sameField = null;
    /**
     * Поле для сравнения на отличие.
     */
    private ?string $differentField = null;
    /**
     * Поле для сравнения "больше".
     */
    private ?string $greaterThanField = null;
    /**
     * Поле для сравнения "больше или равно".
     */
    private ?string $greaterThanOrEqualField = null;
    /**
     * Поле для сравнения "меньше".
     */
    private ?string $lessThanField = null;
    /**
     * Поле для сравнения "меньше или равно".
     */
    private ?string $lessThanOrEqualField = null;

    public function min(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(int $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function setMin(int $min): self
    {
        return $this->min($min);
    }

    public function setMax(int $max): self
    {
        return $this->max($max);
    }

    /**
     * Значение должно быть в диапазоне.
     */
    public function between(int $min, int $max): self
    {
        $this->betweenMin = $min;
        $this->betweenMax = $max;

        return $this;
    }

    /**
     * Значение должно быть равно размеру.
     */
    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Количество цифр должно быть равно.
     */
    public function digits(int $digits): self
    {
        $this->digits = $digits;

        return $this;
    }

    /**
     * Минимальное количество цифр.
     */
    public function minDigits(int $min): self
    {
        $this->minDigits = $min;

        return $this;
    }

    /**
     * Максимальное количество цифр.
     */
    public function maxDigits(int $max): self
    {
        $this->maxDigits = $max;

        return $this;
    }

    /**
     * Количество цифр должно быть в диапазоне.
     */
    public function digitsBetween(int $min, int $max): self
    {
        $this->digitsBetween = ['min' => $min, 'max' => $max];

        return $this;
    }

    /**
     * Значение должно быть кратно числу.
     */
    public function multipleOf(int $divisor): self
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('Делитель не может быть равен 0.');
        }

        $this->multipleOf = $divisor;

        return $this;
    }

    /**
     * Значение должно быть в списке.
     */
    public function in(int ...$values): self
    {
        $this->in = $values;

        return $this;
    }

    /**
     * Значение равно значению другого поля.
     */
    public function same(string $field): self
    {
        $this->sameField = $field;

        return $this;
    }

    /**
     * Значение отличается от значения другого поля.
     */
    public function different(string $field): self
    {
        $this->differentField = $field;

        return $this;
    }

    /**
     * Значение больше значения другого поля.
     */
    public function greaterThan(string $field): self
    {
        $this->greaterThanField = $field;

        return $this;
    }

    /**
     * Значение больше или равно значению другого поля.
     */
    public function greaterThanOrEqual(string $field): self
    {
        $this->greaterThanOrEqualField = $field;

        return $this;
    }

    /**
     * Значение меньше значения другого поля.
     */
    public function lessThan(string $field): self
    {
        $this->lessThanField = $field;

        return $this;
    }

    /**
     * Значение меньше или равно значению другого поля.
     */
    public function lessThanOrEqual(string $field): self
    {
        $this->lessThanOrEqualField = $field;

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        $parsed = $this->parseInt($value);
        if ($parsed === null) {
            return [new ValidationViolation(ValidationEnum::INTEGER->value)];
        }

        $int    = $parsed['value'];
        $raw    = $parsed['raw'];
        $digits = $this->countDigits($raw);

        $violations = [];

        if ($this->min !== null && $int < $this->min) {
            $violations[] = new ValidationViolation(ValidationEnum::MIN->value, ['min' => $this->min, 'value' => $int]);
        }

        if ($this->max !== null && $int > $this->max) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX->value, ['max' => $this->max, 'value' => $int]);
        }

        if ($this->betweenMin !== null && $this->betweenMax !== null) {
            if ($int < $this->betweenMin || $int > $this->betweenMax) {
                $violations[] = new ValidationViolation(ValidationEnum::BETWEEN->value, [
                    'min' => $this->betweenMin,
                    'max' => $this->betweenMax,
                ]);
            }
        }

        if ($this->size !== null && $int !== $this->size) {
            $violations[] = new ValidationViolation(ValidationEnum::SIZE->value, ['size' => $this->size, 'value' => $int]);
        }

        if ($this->digits !== null && $digits !== $this->digits) {
            $violations[] = new ValidationViolation(ValidationEnum::DIGITS->value, ['digits' => $this->digits]);
        }

        if ($this->minDigits !== null && $digits < $this->minDigits) {
            $violations[] = new ValidationViolation(ValidationEnum::MIN_DIGITS->value, ['min' => $this->minDigits]);
        }

        if ($this->maxDigits !== null && $digits > $this->maxDigits) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX_DIGITS->value, ['max' => $this->maxDigits]);
        }

        if ($this->digitsBetween !== null) {
            $min = $this->digitsBetween['min'];
            $max = $this->digitsBetween['max'];
            if ($digits < $min || $digits > $max) {
                $violations[] = new ValidationViolation(ValidationEnum::DIGITS_BETWEEN->value, [
                    'min' => $min,
                    'max' => $max,
                ]);
            }
        }

        if ($this->multipleOf !== null && $int % $this->multipleOf !== 0) {
            $violations[] = new ValidationViolation(ValidationEnum::MULTIPLE_OF->value, ['multiple' => $this->multipleOf]);
        }

        if ($this->in !== null && !in_array($int, $this->in, true)) {
            $violations[] = new ValidationViolation(ValidationEnum::IN->value, ['values' => $this->in]);
        }

        if ($this->sameField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->sameField));
            if ($otherParsed === null || $otherParsed['value'] !== $int) {
                $violations[] = new ValidationViolation(ValidationEnum::SAME->value, ['other' => $this->sameField]);
            }
        }

        if ($this->differentField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->differentField));
            if ($otherParsed !== null && $otherParsed['value'] === $int) {
                $violations[] = new ValidationViolation(ValidationEnum::DIFFERENT->value, ['other' => $this->differentField]);
            }
        }

        if ($this->greaterThanField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->greaterThanField));
            if ($otherParsed === null || $int <= $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::GREATER_THAN->value, ['other' => $this->greaterThanField]);
            }
        }

        if ($this->greaterThanOrEqualField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->greaterThanOrEqualField));
            if ($otherParsed === null || $int < $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::GREATER_THAN_OR_EQUAL->value, ['other' => $this->greaterThanOrEqualField]);
            }
        }

        if ($this->lessThanField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->lessThanField));
            if ($otherParsed === null || $int >= $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::LESS_THAN->value, ['other' => $this->lessThanField]);
            }
        }

        if ($this->lessThanOrEqualField !== null) {
            $otherParsed = $this->parseInt(DataPath::get($data, $this->lessThanOrEqualField));
            if ($otherParsed === null || $int > $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::LESS_THAN_OR_EQUAL->value, ['other' => $this->lessThanOrEqualField]);
            }
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::INTEGER->value               => 'Поле {field} должно быть целым числом.',
            ValidationEnum::MIN->value                   => 'Поле {field} должно быть не меньше {min}.',
            ValidationEnum::MAX->value                   => 'Поле {field} должно быть не больше {max}.',
            ValidationEnum::BETWEEN->value               => 'Поле {field} должно быть между {min} и {max}.',
            ValidationEnum::SIZE->value                  => 'Поле {field} должно быть равно {size}.',
            ValidationEnum::DIGITS->value                => 'Поле {field} должно содержать {digits} цифр.',
            ValidationEnum::MIN_DIGITS->value            => 'Поле {field} должно содержать не меньше {min} цифр.',
            ValidationEnum::MAX_DIGITS->value            => 'Поле {field} должно содержать не больше {max} цифр.',
            ValidationEnum::DIGITS_BETWEEN->value        => 'Поле {field} должно содержать от {min} до {max} цифр.',
            ValidationEnum::MULTIPLE_OF->value           => 'Поле {field} должно быть кратно {multiple}.',
            ValidationEnum::IN->value                    => 'Поле {field} должно быть одним из {values}.',
            ValidationEnum::SAME->value                  => 'Поле {field} должно совпадать с {other}.',
            ValidationEnum::DIFFERENT->value             => 'Поле {field} должно отличаться от {other}.',
            ValidationEnum::GREATER_THAN->value          => 'Поле {field} должно быть больше {other}.',
            ValidationEnum::GREATER_THAN_OR_EQUAL->value => 'Поле {field} должно быть больше или равно {other}.',
            ValidationEnum::LESS_THAN->value             => 'Поле {field} должно быть меньше {other}.',
            ValidationEnum::LESS_THAN_OR_EQUAL->value    => 'Поле {field} должно быть меньше или равно {other}.',
        ];
    }

    /**
     * @return array{value: int, raw: string}|null
     */
    private function parseInt(mixed $value): ?array
    {
        if (is_int($value)) {
            return ['value' => $value, 'raw' => (string) $value];
        }

        if (is_float($value)) {
            if ((int) $value !== $value) {
                return null;
            }

            $raw = $this->normalizeFloat($value);

            return ['value' => (int) $value, 'raw' => $raw];
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || preg_match('/^[+-]?\d+$/', $trimmed) !== 1) {
                return null;
            }

            return ['value' => (int) $trimmed, 'raw' => $trimmed];
        }

        if (is_numeric($value)) {
            $raw = (string) $value;
            if (preg_match('/^[+-]?\d+$/', $raw) !== 1) {
                return null;
            }

            return ['value' => (int) $raw, 'raw' => $raw];
        }

        return null;
    }

    private function countDigits(string $raw): int
    {
        $raw = ltrim($raw, '+-');
        $raw = strpbrk($raw, '.') === false ? $raw : str_replace('.', '', $raw);

        return strlen($raw);
    }

    private function normalizeFloat(float $value): string
    {
        $raw = (string) $value;
        if (strpbrk($raw, 'eE') !== false) {
            $raw = sprintf('%.14F', $value);
        }

        return rtrim(rtrim($raw, '0'), '.');
    }
}
