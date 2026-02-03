<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use BackedEnum;
use Closure;
use InvalidArgumentException;
use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;
use ReflectionFunction;

use function checkdnsrr;
use function enum_exists;
use function filter_var;
use function function_exists;
use function in_array;
use function is_string;
use function json_decode;
use function json_last_error;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function parse_url;
use function preg_match;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strtolower;
use function strtoupper;

use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_MAC;
use const FILTER_VALIDATE_URL;
use const JSON_ERROR_NONE;
use const PHP_URL_HOST;

/**
 * Проверяет строковые значения и набор строковых правил (email, regex, startsWith и т.д.).
 */
final class StringValidation extends AbstractRule
{
    /**
     * Минимальная длина строки.
     */
    private ?int $min = null;
    /**
     * Максимальная длина строки.
     */
    private ?int $max = null;
    /**
     * Точная длина строки.
     */
    private ?int $size = null;
    /**
     * Разрешать только буквы.
     */
    private bool $alpha = false;
    /**
     * Разрешать буквы/цифры/дефисы/подчеркивания.
     */
    private bool $alphaDash = false;
    /**
     * Разрешать только буквы и цифры.
     */
    private bool $alphaNumeric = false;
    /**
     * Разрешать только ASCII символы.
     */
    private bool $ascii = false;
    /**
     * Проверять email.
     */
    private bool $email = false;
    /**
     * Проверять URL.
     */
    private bool $url = false;
    /**
     * Проверять активный URL (с резолвом).
     */
    private bool $activeUrl = false;
    /**
     * Проверять IP адрес.
     */
    private bool $ipAddress = false;
    /**
     * Проверять MAC адрес.
     */
    private bool $macAddress = false;
    /**
     * Проверять JSON-строку.
     */
    private bool $json = false;
    /**
     * Требовать нижний регистр.
     */
    private bool $lowercase = false;
    /**
     * Требовать верхний регистр.
     */
    private bool $uppercase = false;
    /**
     * Проверять HEX-цвет.
     */
    private bool $hexColor = false;
    /**
     * Проверять UUID.
     */
    private bool $uuid = false;
    /**
     * Проверять ULID.
     */
    private bool $ulid = false;
    /**
     * Допустимые префиксы.
     *
     * @var array<int, string>
     */
    private array $startsWith = [];
    /**
     * Допустимые суффиксы.
     *
     * @var array<int, string>
     */
    private array $endsWith = [];
    /**
     * Запрещенные префиксы.
     *
     * @var array<int, string>
     */
    private array $doesntStartWith = [];
    /**
     * Запрещенные суффиксы.
     *
     * @var array<int, string>
     */
    private array $doesntEndWith = [];
    /**
     * Список допустимых значений.
     *
     * @var array<int, string>|null
     */
    private ?array $in = null;
    /**
     * Список запрещенных значений.
     *
     * @var array<int, string>|null
     */
    private ?array $notIn = null;
    /**
     * Регулярное выражение для совпадения.
     */
    private ?string $regex = null;
    /**
     * Регулярное выражение, которому НЕ должно соответствовать значение.
     */
    private ?string $notRegex = null;
    /**
     * Поле для сравнения на равенство.
     */
    private ?string $sameField = null;
    /**
     * Поле для сравнения на отличие.
     */
    private ?string $differentField = null;
    /**
     * Требовать подтверждение (confirmed).
     */
    private bool $confirmed = false;
    /**
     * Имя поля подтверждения.
     */
    private ?string $confirmationField = null;
    /**
     * Класс enum для проверки.
     *
     * @var class-string|null
     */
    private ?string $enumClass = null;
    /**
     * Callback для проверки текущего пароля.
     */
    private ?Closure $currentPasswordCheck = null;

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
     * Точное количество символов.
     */
    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Только буквы.
     */
    public function alpha(): self
    {
        $this->alpha = true;

        return $this;
    }

    /**
     * Буквы, цифры, дефисы и подчеркивания.
     */
    public function alphaDash(): self
    {
        $this->alphaDash = true;

        return $this;
    }

    /**
     * Буквы и цифры.
     */
    public function alphaNumeric(): self
    {
        $this->alphaNumeric = true;

        return $this;
    }

    /**
     * Только ASCII символы.
     */
    public function ascii(): self
    {
        $this->ascii = true;

        return $this;
    }

    /**
     * Валидный email.
     */
    public function email(): self
    {
        $this->email = true;

        return $this;
    }

    /**
     * Валидный URL.
     */
    public function url(): self
    {
        $this->url = true;

        return $this;
    }

    /**
     * Активный URL (валидный + активный хост).
     */
    public function activeUrl(): self
    {
        $this->activeUrl = true;

        return $this;
    }

    /**
     * Валидный IP адрес.
     */
    public function ipAddress(): self
    {
        $this->ipAddress = true;

        return $this;
    }

    /**
     * Валидный MAC адрес.
     */
    public function macAddress(): self
    {
        $this->macAddress = true;

        return $this;
    }

    /**
     * Валидный JSON.
     */
    public function json(): self
    {
        $this->json = true;

        return $this;
    }

    /**
     * Только нижний регистр.
     */
    public function lowercase(): self
    {
        $this->lowercase = true;

        return $this;
    }

    /**
     * Только верхний регистр.
     */
    public function uppercase(): self
    {
        $this->uppercase = true;

        return $this;
    }

    /**
     * Hex‑цвет (#RGB или #RRGGBB).
     */
    public function hexColor(): self
    {
        $this->hexColor = true;

        return $this;
    }

    /**
     * UUID.
     */
    public function uuid(): self
    {
        $this->uuid = true;

        return $this;
    }

    /**
     * ULID.
     */
    public function ulid(): self
    {
        $this->ulid = true;

        return $this;
    }

    /**
     * Значение начинается с одного из префиксов.
     */
    public function startsWith(string ...$needles): self
    {
        $this->startsWith = $needles;

        return $this;
    }

    /**
     * Значение оканчивается одним из суффиксов.
     */
    public function endsWith(string ...$needles): self
    {
        $this->endsWith = $needles;

        return $this;
    }

    /**
     * Значение не должно начинаться с указанных префиксов.
     */
    public function doesntStartWith(string ...$needles): self
    {
        $this->doesntStartWith = $needles;

        return $this;
    }

    /**
     * Значение не должно оканчиваться указанными суффиксами.
     */
    public function doesntEndWith(string ...$needles): self
    {
        $this->doesntEndWith = $needles;

        return $this;
    }

    /**
     * Значение должно быть в списке.
     */
    public function in(string ...$values): self
    {
        $this->in = $values;

        return $this;
    }

    /**
     * Значение не должно быть в списке.
     */
    public function notIn(string ...$values): self
    {
        $this->notIn = $values;

        return $this;
    }

    /**
     * Соответствие регулярному выражению.
     */
    public function regex(string $pattern): self
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException('Некорректное регулярное выражение.');
        }

        $this->regex = $pattern;

        return $this;
    }

    /**
     * Не соответствует регулярному выражению.
     */
    public function notRegex(string $pattern): self
    {
        if (@preg_match($pattern, '') === false) {
            throw new InvalidArgumentException('Некорректное регулярное выражение.');
        }

        $this->notRegex = $pattern;

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
     * Значение совпадает с полем подтверждения.
     */
    public function confirmed(?string $field = null): self
    {
        $this->confirmed         = true;
        $this->confirmationField = $field;

        return $this;
    }

    /**
     * Значение соответствует текущему паролю.
     * Callback получает значение и массив данных.
     */
    public function currentPassword(callable $checker): self
    {
        $this->currentPasswordCheck = $checker;

        return $this;
    }

    /**
     * Значение входит в enum.
     * @param class-string $enumClass
     */
    public function enumClass(string $enumClass): self
    {
        if (!enum_exists($enumClass)) {
            throw new InvalidArgumentException('Ожидался enum класс.');
        }

        $this->enumClass = $enumClass;

        return $this;
    }

    public function validate(mixed $value, string $field, bool $present, array $data): array
    {
        if (!is_string($value)) {
            return [new ValidationViolation(ValidationEnum::STRING->value)];
        }

        $len        = $this->length($value);
        $violations = [];

        if ($this->min !== null && $len < $this->min) {
            $violations[] = new ValidationViolation(ValidationEnum::MIN->value, ['min' => $this->min, 'len' => $len]);
        }

        if ($this->max !== null && $len > $this->max) {
            $violations[] = new ValidationViolation(ValidationEnum::MAX->value, ['max' => $this->max, 'len' => $len]);
        }

        if ($this->size !== null && $len !== $this->size) {
            $violations[] = new ValidationViolation(ValidationEnum::SIZE->value, ['size' => $this->size, 'len' => $len]);
        }

        if ($this->alpha && !preg_match('/^\p{L}+$/u', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ALPHA->value);
        }

        if ($this->alphaDash && !preg_match('/^[\p{L}\p{N}_-]+$/u', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ALPHA_DASH->value);
        }

        if ($this->alphaNumeric && !preg_match('/^[\p{L}\p{N}]+$/u', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ALPHA_NUMERIC->value);
        }

        if ($this->ascii && !preg_match('/^[\x00-\x7F]*$/', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ASCII->value);
        }

        if ($this->email && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $violations[] = new ValidationViolation(ValidationEnum::EMAIL->value);
        }

        if ($this->url && filter_var($value, FILTER_VALIDATE_URL) === false) {
            $violations[] = new ValidationViolation(ValidationEnum::URL->value);
        }

        if ($this->activeUrl && !$this->isActiveUrl($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ACTIVE_URL->value);
        }

        if ($this->ipAddress && filter_var($value, FILTER_VALIDATE_IP) === false) {
            $violations[] = new ValidationViolation(ValidationEnum::IP_ADDRESS->value);
        }

        if ($this->macAddress && filter_var($value, FILTER_VALIDATE_MAC) === false) {
            $violations[] = new ValidationViolation(ValidationEnum::MAC_ADDRESS->value);
        }

        if ($this->json && !$this->isJson($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::JSON->value);
        }

        if ($this->lowercase && $value !== $this->toLower($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::LOWERCASE->value);
        }

        if ($this->uppercase && $value !== $this->toUpper($value)) {
            $violations[] = new ValidationViolation(ValidationEnum::UPPERCASE->value);
        }

        if ($this->hexColor && !preg_match('/^#?([a-f0-9]{3}|[a-f0-9]{6})$/i', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::HEX_COLOR->value);
        }

        if ($this->uuid && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::UUID->value);
        }

        if ($this->ulid && !preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            $violations[] = new ValidationViolation(ValidationEnum::ULID->value);
        }

        if ($this->startsWith !== [] && !$this->startsWithAny($value, $this->startsWith)) {
            $violations[] = new ValidationViolation(ValidationEnum::STARTS_WITH->value, ['values' => $this->startsWith]);
        }

        if ($this->endsWith !== [] && !$this->endsWithAny($value, $this->endsWith)) {
            $violations[] = new ValidationViolation(ValidationEnum::ENDS_WITH->value, ['values' => $this->endsWith]);
        }

        if ($this->doesntStartWith !== [] && $this->startsWithAny($value, $this->doesntStartWith)) {
            $violations[] = new ValidationViolation(ValidationEnum::DOESNT_START_WITH->value, ['values' => $this->doesntStartWith]);
        }

        if ($this->doesntEndWith !== [] && $this->endsWithAny($value, $this->doesntEndWith)) {
            $violations[] = new ValidationViolation(ValidationEnum::DOESNT_END_WITH->value, ['values' => $this->doesntEndWith]);
        }

        if ($this->in !== null && !in_array($value, $this->in, true)) {
            $violations[] = new ValidationViolation(ValidationEnum::IN->value, ['values' => $this->in]);
        }

        if ($this->notIn !== null && in_array($value, $this->notIn, true)) {
            $violations[] = new ValidationViolation(ValidationEnum::NOT_IN->value, ['values' => $this->notIn]);
        }

        if ($this->regex !== null && preg_match($this->regex, $value) !== 1) {
            $violations[] = new ValidationViolation(ValidationEnum::REGEX->value, ['pattern' => $this->regex]);
        }

        if ($this->notRegex !== null && preg_match($this->notRegex, $value) === 1) {
            $violations[] = new ValidationViolation(ValidationEnum::NOT_REGEX->value, ['pattern' => $this->notRegex]);
        }

        if ($this->sameField !== null) {
            $other = DataPath::get($data, $this->sameField);
            if ($other !== $value) {
                $violations[] = new ValidationViolation(ValidationEnum::SAME->value, ['other' => $this->sameField]);
            }
        }

        if ($this->differentField !== null) {
            $other = DataPath::get($data, $this->differentField);
            if ($other === $value) {
                $violations[] = new ValidationViolation(ValidationEnum::DIFFERENT->value, ['other' => $this->differentField]);
            }
        }

        if ($this->confirmed) {
            $confirmationField = $this->confirmationField ?? ($field . '_confirmation');
            $confirmationValue = DataPath::get($data, $confirmationField);
            if ($confirmationValue !== $value) {
                $violations[] = new ValidationViolation(ValidationEnum::CONFIRMED->value, ['other' => $confirmationField]);
            }
        }

        if ($this->currentPasswordCheck !== null) {
            $checker = $this->currentPasswordCheck;
            if ($this->callPasswordChecker($checker, $value, $data) !== true) {
                $violations[] = new ValidationViolation(ValidationEnum::CURRENT_PASSWORD->value);
            }
        }

        if ($this->enumClass !== null && !$this->inEnum($value, $this->enumClass)) {
            $violations[] = new ValidationViolation(ValidationEnum::ENUM->value, [
                'values' => $this->enumValues($this->enumClass),
            ]);
        }

        return $violations;
    }

    public function messages(): array
    {
        return [
            ValidationEnum::STRING->value            => 'Поле {field} должно быть строкой.',
            ValidationEnum::MIN->value               => 'Длина поля {field} должна быть не меньше {min}.',
            ValidationEnum::MAX->value               => 'Длина поля {field} должна быть не больше {max}.',
            ValidationEnum::SIZE->value              => 'Длина поля {field} должна быть равна {size}.',
            ValidationEnum::ALPHA->value             => 'Поле {field} должно содержать только буквы.',
            ValidationEnum::ALPHA_DASH->value        => 'Поле {field} должно содержать только буквы, цифры, дефис и подчеркивание.',
            ValidationEnum::ALPHA_NUMERIC->value     => 'Поле {field} должно содержать только буквы и цифры.',
            ValidationEnum::ASCII->value             => 'Поле {field} должно содержать только ASCII символы.',
            ValidationEnum::EMAIL->value             => 'Поле {field} должно быть корректным email.',
            ValidationEnum::URL->value               => 'Поле {field} должно быть корректным URL.',
            ValidationEnum::ACTIVE_URL->value        => 'Поле {field} должно быть активным URL.',
            ValidationEnum::IP_ADDRESS->value        => 'Поле {field} должно быть корректным IP адресом.',
            ValidationEnum::MAC_ADDRESS->value       => 'Поле {field} должно быть корректным MAC адресом.',
            ValidationEnum::JSON->value              => 'Поле {field} должно быть корректным JSON.',
            ValidationEnum::LOWERCASE->value         => 'Поле {field} должно быть в нижнем регистре.',
            ValidationEnum::UPPERCASE->value         => 'Поле {field} должно быть в верхнем регистре.',
            ValidationEnum::HEX_COLOR->value         => 'Поле {field} должно быть корректным hex‑цветом.',
            ValidationEnum::UUID->value              => 'Поле {field} должно быть корректным UUID.',
            ValidationEnum::ULID->value              => 'Поле {field} должно быть корректным ULID.',
            ValidationEnum::STARTS_WITH->value       => 'Поле {field} должно начинаться с {values}.',
            ValidationEnum::ENDS_WITH->value         => 'Поле {field} должно заканчиваться на {values}.',
            ValidationEnum::DOESNT_START_WITH->value => 'Поле {field} не должно начинаться с {values}.',
            ValidationEnum::DOESNT_END_WITH->value   => 'Поле {field} не должно заканчиваться на {values}.',
            ValidationEnum::IN->value                => 'Поле {field} должно быть одним из {values}.',
            ValidationEnum::NOT_IN->value            => 'Поле {field} не должно быть одним из {values}.',
            ValidationEnum::REGEX->value             => 'Поле {field} не соответствует формату.',
            ValidationEnum::NOT_REGEX->value         => 'Поле {field} соответствует запрещенному формату.',
            ValidationEnum::SAME->value              => 'Поле {field} должно совпадать с {other}.',
            ValidationEnum::DIFFERENT->value         => 'Поле {field} должно отличаться от {other}.',
            ValidationEnum::CONFIRMED->value         => 'Поле {field} не совпадает с подтверждением {other}.',
            ValidationEnum::CURRENT_PASSWORD->value  => 'Поле {field} не совпадает с текущим паролем.',
            ValidationEnum::ENUM->value              => 'Поле {field} должно быть одним из {values}.',
        ];
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function toLower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }

    private function toUpper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
    }

    /**
     * @param array<int, string> $needles
     */
    private function startsWithAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_starts_with($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $needles
     */
    private function endsWithAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_ends_with($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isJson(string $value): bool
    {
        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    private function isActiveUrl(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (!function_exists('checkdnsrr')) {
            return false;
        }

        return checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA') || checkdnsrr($host, 'CNAME');
    }

    private function callPasswordChecker(callable $checker, string $value, array $data): bool
    {
        $ref = new ReflectionFunction(Closure::fromCallable($checker));

        if ($ref->getNumberOfParameters() <= 1) {
            return (bool) $checker($value);
        }

        return (bool) $checker($value, $data);
    }

    /**
     * @param class-string $enumClass
     */
    private function inEnum(string $value, string $enumClass): bool
    {
        $cases = $enumClass::cases();
        foreach ($cases as $case) {
            if ($case instanceof BackedEnum) {
                if ($case->value === $value) {
                    return true;
                }
            } else {
                if ($case->name === $value) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param class-string $enumClass
     * @return array<int, string>
     */
    private function enumValues(string $enumClass): array
    {
        $cases  = $enumClass::cases();
        $values = [];
        foreach ($cases as $case) {
            if ($case instanceof BackedEnum) {
                $values[] = (string) $case->value;
            } else {
                $values[] = $case->name;
            }
        }

        return $values;
    }
}
