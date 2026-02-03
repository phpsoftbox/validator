<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\ArrayValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(ArrayValidation::class)]
final class ValidatorArrayRulesTest extends TestCase
{
    /**
     * Проверяет правила between и size для массивов.
     */
    #[Test]
    public function arrayBetweenAndSizeRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'tags' => [new ArrayValidation()->between(2, 3)->size(2)],
        ];

        $result = $validator->validate(['tags' => [1]], $rules);

        self::assertSame(
            ['Количество элементов в tags должно быть между 2 и 3.', 'Количество элементов в tags должно быть равно 2.'],
            $result->errorBag()->get('tags'),
        );
    }

    /**
     * Проверяет правило contains.
     */
    #[Test]
    public function arrayContainsRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'tags' => [new ArrayValidation()->contains('a', 'b')],
        ];

        $result = $validator->validate(['tags' => ['a']], $rules);

        self::assertSame(['Поле tags должно содержать ["a","b"].'], $result->errorBag()->get('tags'));
    }

    /**
     * Проверяет правила doesnt_contain и distinct.
     */
    #[Test]
    public function arrayDoesntContainAndDistinctRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'items' => [new ArrayValidation()->distinct()->doesntContain('x')],
        ];

        $result = $validator->validate(['items' => ['x', 'x']], $rules);

        self::assertSame(
            ['Поле items должно содержать уникальные значения.', 'Поле items не должно содержать ["x"].'],
            $result->errorBag()->get('items'),
        );
    }

    /**
     * Проверяет правило list.
     */
    #[Test]
    public function arrayListRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'items' => [new ArrayValidation()->listOnly()],
        ];

        $result = $validator->validate(['items' => [1 => 'a', 2 => 'b']], $rules);

        self::assertSame(['Поле items должно быть списком.'], $result->errorBag()->get('items'));
    }

    /**
     * Проверяет правило in_array.
     */
    #[Test]
    public function arrayInArrayRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'subset' => [new ArrayValidation()->inArray('allowed.*')],
        ];

        $result = $validator->validate([
            'subset'  => [1, 2],
            'allowed' => [1, 3],
        ], $rules);

        self::assertSame(['Поле subset должно присутствовать в значениях allowed.*.'], $result->errorBag()->get('subset'));
    }

    /**
     * Проверяет правило in_array_keys.
     */
    #[Test]
    public function arrayInArrayKeysRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'keys' => [new ArrayValidation()->inArrayKeys('timezone')],
        ];

        $result = $validator->validate([
            'keys' => ['mode' => 'strict'],
        ], $rules);

        self::assertSame(['Поле keys должно иметь хотя бы один ключ из ["timezone"].'], $result->errorBag()->get('keys'));
    }
}
