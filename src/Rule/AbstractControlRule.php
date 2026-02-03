<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

use LogicException;

use function sprintf;

/**
 * Базовый класс для управляющих правил (не выполняют проверку значения).
 */
abstract class AbstractControlRule extends AbstractRule
{
    /**
     * Метод required не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function required(bool $required = true): static
    {
        throw $this->unsupported('required');
    }

    /**
     * Метод nullable не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function nullable(bool $nullable = true): static
    {
        throw $this->unsupported('nullable');
    }

    /**
     * Метод requiredIf не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredIf(callable $callback, string $other = 'условие'): static
    {
        throw $this->unsupported('requiredIf');
    }

    /**
     * Метод requiredIfAccepted не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredIfAccepted(string $field): static
    {
        throw $this->unsupported('requiredIfAccepted');
    }

    /**
     * Метод requiredIfDeclined не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredIfDeclined(string $field): static
    {
        throw $this->unsupported('requiredIfDeclined');
    }

    /**
     * Метод requiredUnless не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredUnless(string $field, mixed ...$values): static
    {
        throw $this->unsupported('requiredUnless');
    }

    /**
     * Метод requiredWith не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredWith(string ...$fields): static
    {
        throw $this->unsupported('requiredWith');
    }

    /**
     * Метод requiredWithAll не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredWithAll(string ...$fields): static
    {
        throw $this->unsupported('requiredWithAll');
    }

    /**
     * Метод requiredWithout не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredWithout(string ...$fields): static
    {
        throw $this->unsupported('requiredWithout');
    }

    /**
     * Метод requiredWithoutAll не поддерживается для управляющих правил.
     * @throws LogicException
     */
    public function requiredWithoutAll(string ...$fields): static
    {
        throw $this->unsupported('requiredWithoutAll');
    }

    /**
     * Создать исключение для неподдерживаемого метода.
     */
    protected function unsupported(string $method): LogicException
    {
        return new LogicException(sprintf('Метод %s не поддерживается для %s.', $method, static::class));
    }
}
