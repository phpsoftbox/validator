<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\AbstractFormValidation;
use PhpSoftBox\Validator\ValidationOptions;
use PhpSoftBox\Validator\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(AbstractFormValidation::class)]
final class AbstractFormValidationGettersTest extends TestCase
{
    /**
     * Проверяет получение типизированных значений из validated payload.
     */
    #[Test]
    public function returnsTypedValuesFromPayload(): void
    {
        $form = $this->makeForm([
            'user' => [
                'name'      => 'John',
                'age'       => 42,
                'is_active' => true,
                'rating'    => 4.75,
                'tags'      => ['a', 'b'],
            ],
            'meta' => [
                'code' => 'X1',
            ],
        ]);

        $form->validationResult();

        self::assertSame('John', $form->getString('user.name'));
        self::assertSame('John', $form->getNullableString('user.name'));
        self::assertSame(42, $form->getInt('user.age'));
        self::assertSame(42, $form->getNullableInt('user.age'));
        self::assertTrue($form->getBool('user.is_active'));
        self::assertTrue($form->getNullableBool('user.is_active'));
        self::assertSame(4.75, $form->getFloat('user.rating'));
        self::assertSame(4.75, $form->getNullableFloat('user.rating'));
        self::assertSame(['a', 'b'], $form->getArray('user.tags'));
        self::assertSame('X1', $form->get('meta.code'));
    }

    /**
     * Проверяет default-значения для отсутствующих полей.
     */
    #[Test]
    public function returnsDefaultsForMissingFields(): void
    {
        $form = $this->makeForm([]);
        $form->validationResult();

        self::assertSame('guest', $form->getString('user.name', 'guest'));
        self::assertNull($form->getNullableString('user.name'));
        self::assertSame(7, $form->getInt('user.age', 7));
        self::assertNull($form->getNullableInt('user.age'));
        self::assertTrue($form->getBool('user.is_active', true));
        self::assertNull($form->getNullableBool('user.is_active'));
        self::assertSame(1.5, $form->getFloat('user.rating', 1.5));
        self::assertNull($form->getNullableFloat('user.rating'));
        self::assertSame(['x' => 1], $form->getArray('user.tags', ['x' => 1]));
    }

    /**
     * Проверяет, что float-геттеры принимают целые значения.
     */
    #[Test]
    public function floatGettersAcceptIntValues(): void
    {
        $form = $this->makeForm([
            'price'          => 5,
            'nullable_price' => 9,
        ]);

        $form->validationResult();

        self::assertSame(5.0, $form->getFloat('price'));
        self::assertSame(9.0, $form->getNullableFloat('nullable_price'));
    }

    /**
     * Проверяет ошибки типов для всех typed-getters.
     */
    #[Test]
    public function throwsUnexpectedValueExceptionForWrongTypes(): void
    {
        $form = $this->makeForm([
            'string_field'          => 123,
            'nullable_string_field' => 123,
            'int_field'             => '123',
            'nullable_int_field'    => '123',
            'bool_field'            => 'true',
            'nullable_bool_field'   => 1,
            'float_field'           => '1.23',
            'nullable_float_field'  => '1.23',
            'array_field'           => 'not-array',
        ]);

        $form->validationResult();

        $this->assertTypeException(
            fn () => $form->getString('string_field'),
            'string_field',
            'string',
        );
        $this->assertTypeException(
            fn () => $form->getNullableString('nullable_string_field'),
            'nullable_string_field',
            'string|null',
        );
        $this->assertTypeException(
            fn () => $form->getInt('int_field'),
            'int_field',
            'int',
        );
        $this->assertTypeException(
            fn () => $form->getNullableInt('nullable_int_field'),
            'nullable_int_field',
            'int|null',
        );
        $this->assertTypeException(
            fn () => $form->getBool('bool_field'),
            'bool_field',
            'bool',
        );
        $this->assertTypeException(
            fn () => $form->getNullableBool('nullable_bool_field'),
            'nullable_bool_field',
            'bool|null',
        );
        $this->assertTypeException(
            fn () => $form->getFloat('float_field'),
            'float_field',
            'float',
        );
        $this->assertTypeException(
            fn () => $form->getNullableFloat('nullable_float_field'),
            'nullable_float_field',
            'float|null',
        );
        $this->assertTypeException(
            fn () => $form->getArray('array_field'),
            'array_field',
            'array',
        );
    }

    /**
     * @param callable(): mixed $callback
     */
    private function assertTypeException(callable $callback, string $field, string $expectedType): void
    {
        try {
            $callback();
            self::fail('Expected UnexpectedValueException was not thrown.');
        } catch (UnexpectedValueException $exception) {
            self::assertStringContainsString('Field "' . $field . '" expected ' . $expectedType, $exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function makeForm(array $payload): AbstractFormValidation
    {
        return new class ($payload) extends AbstractFormValidation {
            /**
             * @param array<string, mixed> $payload
             */
            public function __construct(
                private readonly array $payload,
            ) {
            }

            public function validate(?ValidationOptions $options = null): array
            {
                return $this->validationResult($options)->filteredData();
            }

            public function validationResult(?ValidationOptions $options = null): ValidationResult
            {
                $result = new ValidationResult([], $this->payload);

                $this->setValidationResult($result);

                return $result;
            }

            public function rules(): array
            {
                return [];
            }
        };
    }
}
