<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\ValidationViolation;

use function array_key_exists;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function method_exists;

/**
 * Базовый класс для правил валидации.
 */
abstract class AbstractRule implements ValidationRuleInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $runtimeData = [];

    private mixed $runtimeContext = null;

    /**
     * Требовать присутствие значения.
     */
    protected bool $required = false;
    /**
     * Разрешать null/пустые значения.
     */
    protected bool $nullable = false;
    /**
     * Условия required_*.
     *
     * @var list<array{rule: string, fn: callable, params: array}>
     */
    protected array $requiredConditions = [];

    /**
     * Пользовательские сообщения для правил.
     *
     * @var array<string, string>
     */
    protected array $customMessages = [];

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function requiredViolation(array $data, mixed $context, string $field): ?ValidationViolation
    {
        if ($this->required) {
            return new ValidationViolation(ValidationEnum::REQUIRED->value);
        }

        foreach ($this->requiredConditions as $condition) {
            $fn = $condition['fn'];
            if ($fn($data, $context, $field) === true) {
                return new ValidationViolation($condition['rule'], $condition['params']);
            }
        }

        return null;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function shouldExclude(array $data, mixed $context, string $field): bool
    {
        return false;
    }

    public function message(ValidationEnum|string $rule, string $message): static
    {
        $key                        = $rule instanceof ValidationEnum ? $rule->value : (string) $rule;
        $this->customMessages[$key] = $message;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function customMessages(): array
    {
        return $this->customMessages;
    }

    public function requiredIf(callable $callback, string $other = 'условие'): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_IF->value,
            function (array $data, mixed $context, string $field) use ($callback): bool {
                if ($context !== null) {
                    return (bool) $callback($context);
                }

                return (bool) $callback($data);
            },
            ['other' => $other],
        );
    }

    public function requiredIfAccepted(string $field): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_IF_ACCEPTED->value,
            function (array $data, mixed $context, string $currentField) use ($field): bool {
                return $this->anyMatch($data, $field, fn (mixed $value): bool => $this->isAccepted($value));
            },
            ['other' => $field],
        );
    }

    public function requiredIfDeclined(string $field): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_IF_DECLINED->value,
            function (array $data, mixed $context, string $currentField) use ($field): bool {
                return $this->anyMatch($data, $field, fn (mixed $value): bool => $this->isDeclined($value));
            },
            ['other' => $field],
        );
    }

    public function requiredUnless(string $field, mixed ...$values): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_UNLESS->value,
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
            ['other' => $field, 'values' => $values],
        );
    }

    public function requiredWith(string ...$fields): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_WITH->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if ($this->isFilled($data, $field)) {
                        return true;
                    }
                }

                return false;
            },
            ['other' => implode(', ', $fields)],
        );
    }

    public function requiredWithAll(string ...$fields): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_WITH_ALL->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if (!$this->isFilled($data, $field)) {
                        return false;
                    }
                }

                return $fields !== [];
            },
            ['other' => implode(', ', $fields)],
        );
    }

    public function requiredWithout(string ...$fields): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_WITHOUT->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if ($this->isMissingOrEmpty($data, $field)) {
                        return true;
                    }
                }

                return false;
            },
            ['other' => implode(', ', $fields)],
        );
    }

    public function requiredWithoutAll(string ...$fields): static
    {
        return $this->requiredCondition(
            ValidationEnum::REQUIRED_WITHOUT_ALL->value,
            function (array $data, mixed $context, string $currentField) use ($fields): bool {
                foreach ($fields as $field) {
                    if (!$this->isMissingOrEmpty($data, $field)) {
                        return false;
                    }
                }

                return $fields !== [];
            },
            ['other' => implode(', ', $fields)],
        );
    }

    protected function requiredCondition(string $rule, callable $fn, array $params = []): static
    {
        $this->requiredConditions[] = ['rule' => $rule, 'fn' => $fn, 'params' => $params];

        return $this;
    }

    protected function anyMatch(array $data, string $field, callable $predicate): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if (!$match->present) {
                continue;
            }
            if ($predicate($match->value) === true) {
                return true;
            }
        }

        return false;
    }

    protected function isFilled(array $data, string $field): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if ($match->present && !$this->isEmptyValue($match->value)) {
                return true;
            }
        }

        return false;
    }

    protected function isMissingOrEmpty(array $data, string $field): bool
    {
        $matches = DataPath::extract($data, $field);
        foreach ($matches as $match) {
            if ($match->present && !$this->isEmptyValue($match->value)) {
                return false;
            }
        }

        return true;
    }

    protected function isAccepted(mixed $value): bool
    {
        return in_array($value, ['yes', 'on', 1, '1', true, 'true'], true);
    }

    protected function isDeclined(mixed $value): bool
    {
        return in_array($value, ['no', 'off', 0, '0', false, 'false'], true);
    }

    protected function isEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return $value === [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setRuntimeState(array $data, mixed $context): void
    {
        $this->runtimeData    = $data;
        $this->runtimeContext = $context;
    }

    protected function route(string $param, mixed $default = null): mixed
    {
        $routeParams = $this->routeParams();
        if (!is_array($routeParams)) {
            return $default;
        }

        return array_key_exists($param, $routeParams) ? $routeParams[$param] : $default;
    }

    protected function context(): mixed
    {
        return $this->runtimeContext;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function routeParams(): ?array
    {
        $routeParams = $this->runtimeData['_route_params'] ?? null;
        if (is_array($routeParams)) {
            return $routeParams;
        }

        $context = $this->runtimeContext;
        if (!is_object($context)) {
            return null;
        }

        if (method_exists($context, 'attributes')) {
            $attributes = $context->attributes();
            if (is_array($attributes)) {
                $rawRouteParams = $attributes['_route_params'] ?? null;
                if (is_array($rawRouteParams)) {
                    return $rawRouteParams;
                }
            }
        }

        if (method_exists($context, 'psr')) {
            $psr = $context->psr();
            if (is_object($psr) && method_exists($psr, 'getAttribute')) {
                $rawRouteParams = $psr->getAttribute('_route_params');
                if (is_array($rawRouteParams)) {
                    return $rawRouteParams;
                }
            }
        }

        return null;
    }
}
