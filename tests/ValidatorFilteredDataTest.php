<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\ArrayValidation;
use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\ValidationResult;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;

#[CoversClass(Validator::class)]
#[CoversMethod(Validator::class, 'validate')]
#[CoversMethod(ValidationResult::class, 'filteredData')]
final class ValidatorFilteredDataTest extends TestCase
{
    /**
     * Проверим, что при ошибке во вложенном поле элемента массива filteredData не содержит
     * ни ошибочное поле, ни непроверенные ключи элементов, ни поля без правил.
     *
     * @see Validator::validate()
     * @see ValidationResult::filteredData()
     */
    #[Test]
    public function nestedErrorKeepsOnlyValidatedFields(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: [
                'items' => [
                    ['id' => 'x', 'owner_id' => 5],
                    ['id' => 2, 'owner_id' => 6],
                ],
                'is_admin' => true,
            ],
            rules: [
                'items'      => [new ArrayValidation()],
                'items.*.id' => [new IntValidation()],
            ],
        );

        // Ошибка только у items.0.id, в результате — только проверенный items.1.id.
        self::assertSame(['items.0.id'], array_keys($result->errors()));
        self::assertSame(['items' => [1 => ['id' => 2]]], $result->filteredData());
    }

    /**
     * Проверим, что при успешной проверке лишние ключи вложенных элементов в filteredData не попадают.
     *
     * @see Validator::validate()
     * @see ValidationResult::filteredData()
     */
    #[Test]
    public function validNestedItemsDropUnknownKeys(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: [
                'user' => ['name' => 'Alex', 'role' => 'admin'],
            ],
            rules: [
                'user'      => [new ArrayValidation()],
                'user.name' => [new StringValidation()],
            ],
        );

        self::assertFalse($result->hasErrors());
        self::assertSame(['user' => ['name' => 'Alex']], $result->filteredData());
    }

    /**
     * Проверим, что массив без правил для вложенных путей попадает в filteredData целиком.
     *
     * @see Validator::validate()
     * @see ValidationResult::filteredData()
     */
    #[Test]
    public function arrayWithoutNestedRulesIsKeptWhole(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['tags' => ['a', 'b']],
            rules: ['tags' => [new ArrayValidation()]],
        );

        self::assertSame(['tags' => ['a', 'b']], $result->filteredData());
    }

    /**
     * Проверим, что при ошибке родительского массива его вложенные поля в filteredData не попадают.
     *
     * @see Validator::validate()
     * @see ValidationResult::filteredData()
     */
    #[Test]
    public function parentErrorDropsNestedFields(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['items' => [['id' => 1], ['id' => 2]], 'name' => 'Alex'],
            rules: [
                'items'      => [new ArrayValidation()->max(1)],
                'items.*.id' => [new IntValidation()],
                'name'       => [new StringValidation()],
            ],
        );

        self::assertSame(['items'], array_keys($result->errors()));
        self::assertSame(['name' => 'Alex'], $result->filteredData());
    }

    /**
     * Проверим, что пустое значение nullable-поля считается валидным и попадает в filteredData.
     *
     * @see Validator::validate()
     * @see ValidationResult::filteredData()
     */
    #[Test]
    public function nullableEmptyValueIsIncluded(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['comment' => null],
            rules: ['comment' => [new StringValidation()->nullable()]],
        );

        self::assertSame(['comment' => null], $result->filteredData());
    }
}
