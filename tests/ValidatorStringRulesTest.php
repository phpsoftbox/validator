<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

enum TestStatus: string
{
    case Active  = 'active';
    case Pending = 'pending';
}

#[CoversClass(Validator::class)]
#[CoversClass(StringValidation::class)]
final class ValidatorStringRulesTest extends TestCase
{
    /**
     * Проверяет, что правило string отклоняет нестроковые значения.
     */
    #[Test]
    public function stringRejectsNonString(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [new StringValidation()],
        ];

        $result = $validator->validate(['value' => 10], $rules);

        self::assertSame(['Поле value должно быть строкой.'], $result->errorBag()->get('value'));
    }

    /**
     * Проверяет базовую валидацию строк и min/max/size.
     */
    #[Test]
    public function stringMinMaxSizeValidation(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [new StringValidation()->min(2)->max(4)->size(3)],
        ];

        $result = $validator->validate(['name' => 'ab'], $rules);

        self::assertSame(
            ['Длина поля name должна быть равна 3.'],
            $result->errorBag()->get('name'),
        );
    }

    /**
     * Проверяет правила alpha/alpha_dash/alpha_numeric/ascii.
     */
    #[Test]
    public function alphaAndAsciiRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'alpha' => [new StringValidation()->alpha()],
            'dash'  => [new StringValidation()->alphaDash()],
            'num'   => [new StringValidation()->alphaNumeric()],
            'ascii' => [new StringValidation()->ascii()],
        ];

        $result = $validator->validate([
            'alpha' => 'abc1',
            'dash'  => 'ab$',
            'num'   => 'ab-',
            'ascii' => 'тест',
        ], $rules);

        self::assertSame(['Поле alpha должно содержать только буквы.'], $result->errorBag()->get('alpha'));
        self::assertSame(
            ['Поле dash должно содержать только буквы, цифры, дефис и подчеркивание.'],
            $result->errorBag()->get('dash'),
        );
        self::assertSame(['Поле num должно содержать только буквы и цифры.'], $result->errorBag()->get('num'));
        self::assertSame(['Поле ascii должно содержать только ASCII символы.'], $result->errorBag()->get('ascii'));
    }

    /**
     * Проверяет правила email/url/active_url.
     */
    #[Test]
    public function emailUrlActiveUrlRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'email'  => [new StringValidation()->email()],
            'url'    => [new StringValidation()->url()],
            'active' => [new StringValidation()->activeUrl()],
        ];

        $result = $validator->validate([
            'email'  => 'not-email',
            'url'    => 'not-url',
            'active' => 'http://127.0.0.1/test',
        ], $rules);

        self::assertSame(['Поле email должно быть корректным email.'], $result->errorBag()->get('email'));
        self::assertSame(['Поле url должно быть корректным URL.'], $result->errorBag()->get('url'));
        self::assertFalse($result->errorBag()->has('active'));
    }

    /**
     * Проверяет правила starts_with/ends_with и отрицательные варианты.
     */
    #[Test]
    public function startsEndsRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'start'    => [new StringValidation()->startsWith('he')],
            'end'      => [new StringValidation()->endsWith('lo')],
            'no_start' => [new StringValidation()->doesntStartWith('he')],
            'no_end'   => [new StringValidation()->doesntEndWith('lo')],
        ];

        $result = $validator->validate([
            'start'    => 'hello',
            'end'      => 'hello',
            'no_start' => 'hello',
            'no_end'   => 'hello',
        ], $rules);

        self::assertFalse($result->errorBag()->has('start'));
        self::assertFalse($result->errorBag()->has('end'));
        self::assertSame(['Поле no_start не должно начинаться с ["he"].'], $result->errorBag()->get('no_start'));
        self::assertSame(['Поле no_end не должно заканчиваться на ["lo"].'], $result->errorBag()->get('no_end'));
    }

    /**
     * Проверяет правила in/not_in/enum.
     */
    #[Test]
    public function inNotInEnumRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'in'   => [new StringValidation()->in('a', 'b')],
            'not'  => [new StringValidation()->notIn('x', 'y')],
            'enum' => [new StringValidation()->enumClass(TestStatus::class)],
        ];

        $result = $validator->validate([
            'in'   => 'c',
            'not'  => 'x',
            'enum' => 'unknown',
        ], $rules);

        self::assertSame(['Поле in должно быть одним из ["a","b"].'], $result->errorBag()->get('in'));
        self::assertSame(['Поле not не должно быть одним из ["x","y"].'], $result->errorBag()->get('not'));
        self::assertSame(['Поле enum должно быть одним из ["active","pending"].'], $result->errorBag()->get('enum'));
    }

    /**
     * Проверяет правила regex и not_regex.
     */
    #[Test]
    public function regexRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'regex' => [new StringValidation()->regex('/^a/')],
            'not'   => [new StringValidation()->notRegex('/^a/')],
        ];

        $result = $validator->validate([
            'regex' => 'bcd',
            'not'   => 'abc',
        ], $rules);

        self::assertSame(['Поле regex не соответствует формату.'], $result->errorBag()->get('regex'));
        self::assertSame(['Поле not соответствует запрещенному формату.'], $result->errorBag()->get('not'));
    }

    /**
     * Проверяет same/different/confirmed.
     */
    #[Test]
    public function sameDifferentConfirmedRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'same'      => [new StringValidation()->same('other')],
            'different' => [new StringValidation()->different('other')],
            'password'  => [new StringValidation()->confirmed()],
        ];

        $result = $validator->validate([
            'same'                  => 'a',
            'different'             => 'b',
            'other'                 => 'b',
            'password'              => 'secret',
            'password_confirmation' => 'mismatch',
        ], $rules);

        self::assertSame(['Поле same должно совпадать с other.'], $result->errorBag()->get('same'));
        self::assertSame(['Поле different должно отличаться от other.'], $result->errorBag()->get('different'));
        self::assertSame(
            ['Поле password не совпадает с подтверждением password_confirmation.'],
            $result->errorBag()->get('password'),
        );
    }

    /**
     * Проверяет current_password.
     */
    #[Test]
    public function currentPasswordRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'password' => [new StringValidation()->currentPassword(fn (string $value): bool => $value === 'secret')],
        ];

        $result = $validator->validate(['password' => 'wrong'], $rules);

        self::assertSame(['Поле password не совпадает с текущим паролем.'], $result->errorBag()->get('password'));
    }

    /**
     * Проверяет json/lowercase/uppercase/hex_color.
     */
    #[Test]
    public function jsonCaseHexRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'json'  => [new StringValidation()->json()],
            'lower' => [new StringValidation()->lowercase()],
            'upper' => [new StringValidation()->uppercase()],
            'hex'   => [new StringValidation()->hexColor()],
        ];

        $result = $validator->validate([
            'json'  => '{',
            'lower' => 'Abc',
            'upper' => 'Abc',
            'hex'   => 'zzz',
        ], $rules);

        self::assertSame(['Поле json должно быть корректным JSON.'], $result->errorBag()->get('json'));
        self::assertSame(['Поле lower должно быть в нижнем регистре.'], $result->errorBag()->get('lower'));
        self::assertSame(['Поле upper должно быть в верхнем регистре.'], $result->errorBag()->get('upper'));
        self::assertSame(['Поле hex должно быть корректным hex‑цветом.'], $result->errorBag()->get('hex'));
    }

    /**
     * Проверяет ip_address/mac_address/uuid/ulid.
     */
    #[Test]
    public function ipMacUuidUlidRules(): void
    {
        $validator = new Validator();
        $rules     = [
            'ip'   => [new StringValidation()->ipAddress()],
            'mac'  => [new StringValidation()->macAddress()],
            'uuid' => [new StringValidation()->uuid()],
            'ulid' => [new StringValidation()->ulid()],
        ];

        $result = $validator->validate([
            'ip'   => 'no-ip',
            'mac'  => '00:11:22:33:44:ZZ',
            'uuid' => 'invalid',
            'ulid' => 'invalid',
        ], $rules);

        self::assertSame(['Поле ip должно быть корректным IP адресом.'], $result->errorBag()->get('ip'));
        self::assertSame(['Поле mac должно быть корректным MAC адресом.'], $result->errorBag()->get('mac'));
        self::assertSame(['Поле uuid должно быть корректным UUID.'], $result->errorBag()->get('uuid'));
        self::assertSame(['Поле ulid должно быть корректным ULID.'], $result->errorBag()->get('ulid'));
    }
}
