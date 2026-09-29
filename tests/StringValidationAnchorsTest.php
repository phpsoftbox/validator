<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StringValidation::class)]
#[CoversMethod(StringValidation::class, 'uuid')]
#[CoversMethod(StringValidation::class, 'alpha')]
#[CoversMethod(StringValidation::class, 'validate')]
final class StringValidationAnchorsTest extends TestCase
{
    /**
     * Проверим, что uuid с завершающим переводом строки отклоняется.
     *
     * @see StringValidation::uuid()
     * @see StringValidation::validate()
     */
    #[Test]
    public function uuidRejectsTrailingNewline(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            ['id' => "123e4567-e89b-12d3-a456-426614174000\n"],
            ['id' => [new StringValidation()->uuid()]],
        );

        self::assertTrue($result->errorBag()->has('id'));
    }

    /**
     * Проверим, что alpha со завершающим переводом строки отклоняется.
     *
     * @see StringValidation::alpha()
     * @see StringValidation::validate()
     */
    #[Test]
    public function alphaRejectsTrailingNewline(): void
    {
        $validator = new Validator();

        $result = $validator->validate(['name' => "abc\n"], ['name' => [new StringValidation()->alpha()]]);

        self::assertTrue($result->errorBag()->has('name'));
    }
}
