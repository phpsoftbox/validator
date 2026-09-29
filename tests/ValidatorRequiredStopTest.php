<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\ValidationOptions;
use PhpSoftBox\Validator\ValidationStopMode;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversMethod(Validator::class, 'validate')]
final class ValidatorRequiredStopTest extends TestCase
{
    /**
     * Проверим, что для пустого обязательного поля возвращается только ошибка required,
     * остальные правила поля не выполняются.
     *
     * @see Validator::validate()
     */
    #[Test]
    public function emptyRequiredFieldReportsOnlyRequired(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['name' => ''],
            rules: ['name' => [new StringValidation()->required()->min(3)]],
        );

        self::assertSame(['Поле name обязательно.'], $result->errorBag()->get('name'));
    }

    /**
     * Проверим, что в режиме FIRST_PER_FIELD пустое обязательное поле даёт одну ошибку,
     * даже если у поля несколько правил.
     *
     * @see Validator::validate()
     */
    #[Test]
    public function emptyRequiredFieldHonorsFirstPerField(): void
    {
        $validator = new Validator();

        $result = $validator->validate(
            data: ['name' => ''],
            rules: [
                'name' => [
                    new StringValidation()->required()->min(3),
                    new StringValidation()->email(),
                ],
            ],
            options: new ValidationOptions(stopMode: ValidationStopMode::FIRST_PER_FIELD),
        );

        self::assertSame(['Поле name обязательно.'], $result->errorBag()->get('name'));
    }
}
