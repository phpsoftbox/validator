<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use InvalidArgumentException;
use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function abs;
use function explode;
use function is_finite;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;
use function ltrim;
use function max;
use function preg_match;
use function preg_replace;
use function round;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function strlen;
use function strpbrk;
use function substr;
use function trim;

/**
 * Проверяет числа с плавающей точкой и связанные ограничения.
 */
final class FloatValidation extends AbstractRule
{
    /**
     * Минимальное значение.
     */
    private ?float $min = null;
    /**
     * Максимальное значение.
     */
    private ?float $max = null;
    /**
     * Минимум в диапазоне between().
     */
    private ?float $betweenMin = null;
    /**
     * Максимум в диапазоне between().
     */
    private ?float $betweenMax = null;
    /**
     * Точное значение (size()).
     */
    private ?float $size = null;
    /**
     * Точное количество цифр в числе.
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
     * Диапазон количества десятичных знаков.
     *
     * @var array{min: int, max: int}|null
     */
    private ?array $decimalBetween = null;
    /**
     * Требовать кратность числу.
     */
    private ?float $multipleOf = null;
    /**
     * Допустимые значения.
     *
     * @var array<int, float>|null
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
    /**
     * Использовать numeric вместо строгого float.
     */
    private bool $useNumericRule = false;

    public function min(float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function setMin(float $min): self
    {
        return $this->min($min);
    }

    public function setMax(float $max): self
    {
        return $this->max($max);
    }

    /**
     * Использовать правило numeric вместо float.
     */
    public function numeric(): self
    {
        $this->useNumericRule = true;

        return $this;
    }

    /**
     * Значение должно быть в диапазоне.
     */
    public function between(float $min, float $max): self
    {
        $this->betweenMin = $min;
        $this->betweenMax = $max;

        return $this;
    }

    /**
     * Значение должно быть равно размеру.
     */
    public function size(float $size): self
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
     * Количество знаков после запятой.
     */
    public function decimal(int $min, ?int $max = null): self
    {
        $this->decimalBetween = ['min' => $min, 'max' => $max ?? $min];

        return $this;
    }

    /**
     * Значение должно быть кратно числу.
     */
    public function multipleOf(float $divisor): self
    {
        if ($divisor == 0.0) {
            throw new InvalidArgumentException('Делитель не может быть равен 0.');
        }

        $this->multipleOf = $divisor;

        return $this;
    }

    /**
     * Значение должно быть в списке.
     */
    public function in(float ...$values): self
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
        $parsed = $this->parseNumber($value);
        if ($parsed === null) {
            $rule = $this->useNumericRule ? ValidationEnum::NUMERIC->value : ValidationEnum::FLOAT->value;

            return [new ValidationViolation($rule)];
        }

        $float         = $parsed['value'];
        $digits        = $parsed['digits'];
        $decimalDigits = $parsed['decimalDigits'];
        $integerLike   = $parsed['isInteger'];
        $violations    = [];

        if ($this->min !== null && $float < $this->min) {
            $violations[] = new ValidationViolation(ValidationEnum::MIN->value, ['min' => $this->min, 'value' => $float]);
        }

        if ($this->max !== null && $float > $this->max) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX->value, ['max' => $this->max, 'value' => $float]);
        }

        if ($this->betweenMin !== null && $this->betweenMax !== null) {
            if ($float < $this->betweenMin || $float > $this->betweenMax) {
                $violations[] = new ValidationViolation(ValidationEnum::BETWEEN->value, [
                    'min' => $this->betweenMin,
                    'max' => $this->betweenMax,
                ]);
            }
        }

        if ($this->size !== null && !$this->floatEquals($float, $this->size)) {
            $violations[] = new ValidationViolation(ValidationEnum::SIZE->value, ['size' => $this->size, 'value' => $float]);
        }

        if ($this->digits !== null) {
            if (!$integerLike || $digits !== $this->digits) {
                $violations[] = new ValidationViolation(ValidationEnum::DIGITS->value, ['digits' => $this->digits]);
            }
        }

        if ($this->minDigits !== null) {
            if (!$integerLike || $digits < $this->minDigits) {
                $violations[] = new ValidationViolation(ValidationEnum::MIN_DIGITS->value, ['min' => $this->minDigits]);
            }
        }

        if ($this->maxDigits !== null) {
            if (!$integerLike || $digits > $this->maxDigits) {
                $violations[] = new ValidationViolation(ValidationEnum::MAX_DIGITS->value, ['max' => $this->maxDigits]);
            }
        }

        if ($this->digitsBetween !== null) {
            $min = $this->digitsBetween['min'];
            $max = $this->digitsBetween['max'];
            if (!$integerLike || $digits < $min || $digits > $max) {
                $violations[] = new ValidationViolation(ValidationEnum::DIGITS_BETWEEN->value, [
                    'min' => $min,
                    'max' => $max,
                ]);
            }
        }

        if ($this->decimalBetween !== null) {
            $min = $this->decimalBetween['min'];
            $max = $this->decimalBetween['max'];
            if ($decimalDigits < $min || $decimalDigits > $max) {
                $violations[] = new ValidationViolation(ValidationEnum::DECIMAL->value, [
                    'min' => $min,
                    'max' => $max,
                ]);
            }
        }

        if ($this->multipleOf !== null && !$this->isMultipleOf($parsed['raw'], $float, $this->multipleOf)) {
            $violations[] = new ValidationViolation(ValidationEnum::MULTIPLE_OF->value, ['multiple' => $this->multipleOf]);
        }

        if ($this->in !== null && !$this->inFloatArray($float, $this->in)) {
            $violations[] = new ValidationViolation(ValidationEnum::IN->value, ['values' => $this->in]);
        }

        if ($this->sameField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->sameField));
            if ($otherParsed === null || !$this->floatEquals($float, $otherParsed['value'])) {
                $violations[] = new ValidationViolation(ValidationEnum::SAME->value, ['other' => $this->sameField]);
            }
        }

        if ($this->differentField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->differentField));
            if ($otherParsed !== null && $this->floatEquals($float, $otherParsed['value'])) {
                $violations[] = new ValidationViolation(ValidationEnum::DIFFERENT->value, ['other' => $this->differentField]);
            }
        }

        if ($this->greaterThanField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->greaterThanField));
            if ($otherParsed === null || $float <= $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::GREATER_THAN->value, ['other' => $this->greaterThanField]);
            }
        }

        if ($this->greaterThanOrEqualField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->greaterThanOrEqualField));
            if ($otherParsed === null || $float < $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::GREATER_THAN_OR_EQUAL->value, ['other' => $this->greaterThanOrEqualField]);
            }
        }

        if ($this->lessThanField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->lessThanField));
            if ($otherParsed === null || $float >= $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::LESS_THAN->value, ['other' => $this->lessThanField]);
            }
        }

        if ($this->lessThanOrEqualField !== null) {
            $otherParsed = $this->parseNumber(DataPath::get($data, $this->lessThanOrEqualField));
            if ($otherParsed === null || $float > $otherParsed['value']) {
                $violations[] = new ValidationViolation(ValidationEnum::LESS_THAN_OR_EQUAL->value, ['other' => $this->lessThanOrEqualField]);
            }
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::FLOAT->value                 => 'Поле {field} должно быть числом.',
            ValidationEnum::NUMERIC->value               => 'Поле {field} должно быть числом.',
            ValidationEnum::MIN->value                   => 'Поле {field} должно быть не меньше {min}.',
            ValidationEnum::MAX->value                   => 'Поле {field} должно быть не больше {max}.',
            ValidationEnum::BETWEEN->value               => 'Поле {field} должно быть между {min} и {max}.',
            ValidationEnum::SIZE->value                  => 'Поле {field} должно быть равно {size}.',
            ValidationEnum::DIGITS->value                => 'Поле {field} должно содержать {digits} цифр.',
            ValidationEnum::MIN_DIGITS->value            => 'Поле {field} должно содержать не меньше {min} цифр.',
            ValidationEnum::MAX_DIGITS->value            => 'Поле {field} должно содержать не больше {max} цифр.',
            ValidationEnum::DIGITS_BETWEEN->value        => 'Поле {field} должно содержать от {min} до {max} цифр.',
            ValidationEnum::DECIMAL->value               => 'Поле {field} должно содержать от {min} до {max} знаков после запятой.',
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
     * @return array{value: float, raw: string, digits: int, decimalDigits: int, isInteger: bool}|null
     */
    private function parseNumber(mixed $value): ?array
    {
        $raw        = null;
        $fromString = false;
        if (is_int($value) || is_float($value)) {
            $raw = (string) $value;
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || !is_numeric($trimmed)) {
                return null;
            }
            $raw        = $trimmed;
            $fromString = true;
        } elseif (is_numeric($value)) {
            $raw = (string) $value;
        } else {
            return null;
        }

        $float  = (float) $raw;
        $source = $raw;
        if (strpbrk($raw, 'eE') !== false) {
            $raw        = sprintf('%.14F', $float);
            $fromString = false;
        }

        // Хвостовые нули убираются только в дробной части: 100 → «100», 1.50 → «1.5».
        if (!$fromString && str_contains($raw, '.')) {
            $raw = rtrim(rtrim($raw, '0'), '.');
        }
        $raw = $raw === '' ? '0' : $raw;

        $signless      = ltrim($raw, '+-');
        $isInteger     = preg_match('/^\d+$/', $signless) === 1;
        $digits        = $this->countDigits($signless);
        $decimalDigits = 0;
        if (!$isInteger) {
            $parts         = explode('.', $signless);
            $decimalDigits = isset($parts[1]) ? strlen($parts[1]) : 0;
        }

        return [
            'value'         => $float,
            'raw'           => $source,
            'digits'        => $digits,
            'decimalDigits' => $decimalDigits,
            'isInteger'     => $isInteger,
        ];
    }

    private function countDigits(string $value): int
    {
        return strlen(preg_replace('/\D/', '', $value));
    }

    /**
     * @param array<int, float> $values
     */
    private function inFloatArray(float $value, array $values): bool
    {
        foreach ($values as $candidate) {
            if ($this->floatEquals($value, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function floatEquals(float $left, float $right): bool
    {
        return abs($left - $right) < 0.000000001;
    }

    /**
     * Проверить кратность в десятичной арифметике: значение и делитель приводятся
     * к целым числам с общим масштабом (0.3 и 0.1 → 3 и 1), поэтому ошибки двоичного
     * представления float не влияют на результат. Если числа не помещаются в int,
     * используется сравнение частного с ближайшим целым с относительной погрешностью.
     */
    private function isMultipleOf(string $raw, float $value, float $divisor): bool
    {
        $valueParts   = $this->decimalParts($raw);
        $divisorParts = $this->decimalParts((string) $divisor);

        if ($valueParts !== null && $divisorParts !== null) {
            $scale         = max($valueParts['scale'], $divisorParts['scale']);
            $valueDigits   = $valueParts['digits'] . str_repeat('0', $scale - $valueParts['scale']);
            $divisorDigits = $divisorParts['digits'] . str_repeat('0', $scale - $divisorParts['scale']);
            $valueDigits   = ltrim($valueDigits, '0');
            $divisorDigits = ltrim($divisorDigits, '0');

            if ($valueDigits === '') {
                return true;
            }

            if ($divisorDigits !== '' && strlen($valueDigits) <= 18 && strlen($divisorDigits) <= 18) {
                return (int) $valueDigits % (int) $divisorDigits === 0;
            }
        }

        $quotient = $value / $divisor;
        if (!is_finite($quotient)) {
            return false;
        }

        return abs($quotient - round($quotient)) <= 1e-9 * max(1.0, abs($quotient));
    }

    /**
     * Разложить десятичную запись числа на цифры без знака и масштаб (число знаков после точки).
     *
     * @return array{digits: string, scale: int}|null
     */
    private function decimalParts(string $number): ?array
    {
        $match = [];
        if (preg_match('/^[+-]?(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d+))?\z/', trim($number), $match) !== 1) {
            return null;
        }

        $integer  = $match[1];
        $fraction = $match[2] ?? '';
        $exponent = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : 0;
        if ($integer === '' && $fraction === '') {
            return null;
        }

        if ($exponent > 30 || $exponent < -30) {
            return null;
        }

        $digits = $integer . $fraction;
        $scale  = strlen($fraction) - $exponent;
        if ($scale < 0) {
            $digits .= str_repeat('0', -$scale);
            $scale = 0;
        }

        // Хвостовые нули дробной части не влияют на значение.
        while ($scale > 0 && str_ends_with($digits, '0')) {
            $digits = substr($digits, 0, -1);
            $scale--;
        }

        return ['digits' => $digits, 'scale' => $scale];
    }
}
