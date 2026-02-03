<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use LogicException;
use PhpSoftBox\Validator\Rule\AnyOfValidation;
use PhpSoftBox\Validator\Rule\BailValidation;
use PhpSoftBox\Validator\Rule\ExcludeValidation;
use PhpSoftBox\Validator\Rule\FilledValidation;
use PhpSoftBox\Validator\Rule\IntValidation;
use PhpSoftBox\Validator\Rule\MissingValidation;
use PhpSoftBox\Validator\Rule\PresentValidation;
use PhpSoftBox\Validator\Rule\ProhibitedValidation;
use PhpSoftBox\Validator\Rule\ProhibitsValidation;
use PhpSoftBox\Validator\Rule\StringValidation;
use PhpSoftBox\Validator\ValidationEnum;
use PhpSoftBox\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
#[CoversClass(StringValidation::class)]
#[CoversClass(PresentValidation::class)]
#[CoversClass(FilledValidation::class)]
#[CoversClass(MissingValidation::class)]
#[CoversClass(ExcludeValidation::class)]
#[CoversClass(BailValidation::class)]
#[CoversClass(AnyOfValidation::class)]
#[CoversClass(ProhibitedValidation::class)]
#[CoversClass(ProhibitsValidation::class)]
#[CoversClass(IntValidation::class)]
final class ValidatorRulesTest extends TestCase
{
    /**
     * Проверяет, что required_with подставляет {other} в сообщение.
     */
    #[Test]
    public function requiredWithUsesOtherPlaceholder(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [new StringValidation()->requiredWith('age')],
        ];
        $messages = [
            'name' => [
                ValidationEnum::REQUIRED_WITH->value => 'Поле {field} нужно из-за {other}.',
            ],
        ];

        $result = $validator->validate(['age' => 20], $rules, $messages);

        self::assertSame(['Поле name нужно из-за age.'], $result->errorBag()->get('name'));
    }

    /**
     * Проверяет правило present.
     */
    #[Test]
    public function presentRuleRequiresPresence(): void
    {
        $validator = new Validator();
        $rules     = [
            'email' => [new PresentValidation()],
        ];

        $result = $validator->validate([], $rules);

        self::assertSame(['Поле email должно присутствовать.'], $result->errorBag()->get('email'));
    }

    /**
     * Проверяет правило present_if.
     */
    #[Test]
    public function presentIfRequiresPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'email' => [new PresentValidation()->presentIf('status', 'active')],
        ];

        $result = $validator->validate(['status' => 'active'], $rules);

        self::assertSame(
            ['Поле email должно присутствовать, если status равно ["active"].'],
            $result->errorBag()->get('email'),
        );
    }

    /**
     * Проверяет правило present_unless.
     */
    #[Test]
    public function presentUnlessRequiresPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'email' => [new PresentValidation()->presentUnless('status', 'active')],
        ];

        $result = $validator->validate(['status' => 'inactive'], $rules);

        self::assertSame(
            ['Поле email должно присутствовать, если status не равно ["active"].'],
            $result->errorBag()->get('email'),
        );
    }

    /**
     * Проверяет правило present_with.
     */
    #[Test]
    public function presentWithRequiresPresenceWhenAnyFieldPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'email' => [new PresentValidation()->presentWith('name')],
        ];

        $result = $validator->validate(['name' => 'Alex'], $rules);

        self::assertSame(
            ['Поле email должно присутствовать при наличии полей name.'],
            $result->errorBag()->get('email'),
        );
    }

    /**
     * Проверяет правило present_with_all.
     */
    #[Test]
    public function presentWithAllRequiresPresenceWhenAllFieldsPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'email' => [new PresentValidation()->presentWithAll('name', 'age')],
        ];

        $result = $validator->validate(['name' => 'Alex', 'age' => 20], $rules);

        self::assertSame(
            ['Поле email должно присутствовать при наличии всех полей name, age.'],
            $result->errorBag()->get('email'),
        );
    }

    /**
     * Проверяет правило filled.
     */
    #[Test]
    public function filledRuleRejectsEmptyValue(): void
    {
        $validator = new Validator();
        $rules     = [
            'title' => [new FilledValidation()],
        ];

        $result = $validator->validate(['title' => ''], $rules);

        self::assertSame(['Поле title не должно быть пустым.'], $result->errorBag()->get('title'));
    }

    /**
     * Проверяет правило missing.
     */
    #[Test]
    public function missingRuleRejectsPresence(): void
    {
        $validator = new Validator();
        $rules     = [
            'token' => [new MissingValidation()],
        ];

        $result = $validator->validate(['token' => 'x'], $rules);

        self::assertSame(['Поле token должно отсутствовать.'], $result->errorBag()->get('token'));
    }

    /**
     * Проверяет правило missing_if.
     */
    #[Test]
    public function missingIfRejectsPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'token' => [new MissingValidation()->missingIf('status', 'banned')],
        ];

        $result = $validator->validate(['status' => 'banned', 'token' => 'x'], $rules);

        self::assertSame(
            ['Поле token должно отсутствовать, если status равно ["banned"].'],
            $result->errorBag()->get('token'),
        );
    }

    /**
     * Проверяет правило missing_unless.
     */
    #[Test]
    public function missingUnlessRejectsPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'token' => [new MissingValidation()->missingUnless('status', 'active')],
        ];

        $result = $validator->validate(['status' => 'inactive', 'token' => 'x'], $rules);

        self::assertSame(
            ['Поле token должно отсутствовать, если status не равно ["active"].'],
            $result->errorBag()->get('token'),
        );
    }

    /**
     * Проверяет правило missing_with.
     */
    #[Test]
    public function missingWithRejectsPresenceWhenAnyFieldPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'token' => [new MissingValidation()->missingWith('guest')],
        ];

        $result = $validator->validate(['guest' => true, 'token' => 'x'], $rules);

        self::assertSame(
            ['Поле token должно отсутствовать при наличии полей guest.'],
            $result->errorBag()->get('token'),
        );
    }

    /**
     * Проверяет правило missing_with_all.
     */
    #[Test]
    public function missingWithAllRejectsPresenceWhenAllFieldsPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'token' => [new MissingValidation()->missingWithAll('a', 'b')],
        ];

        $result = $validator->validate(['a' => 1, 'b' => 2, 'token' => 'x'], $rules);

        self::assertSame(
            ['Поле token должно отсутствовать при наличии всех полей a, b.'],
            $result->errorBag()->get('token'),
        );
    }

    /**
     * Проверяет, что exclude исключает поле из валидации и filteredData.
     */
    #[Test]
    public function excludeSkipsValidation(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [
                new ExcludeValidation(),
                new StringValidation()->min(2),
            ],
        ];

        $result = $validator->validate(['name' => 'a'], $rules);

        self::assertFalse($result->hasErrors());
        self::assertSame([], $result->all());
    }

    /**
     * Проверяет, что exclude_with исключает поле при наличии связанного.
     */
    #[Test]
    public function excludeWithSkipsWhenRelatedPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [
                new ExcludeValidation()->excludeWith('skip'),
                new StringValidation()->min(3),
            ],
        ];

        $result = $validator->validate(['name' => 'a', 'skip' => true], $rules);

        self::assertFalse($result->hasErrors());
        self::assertSame([], $result->all());
    }

    /**
     * Проверяет, что bail останавливает проверку после первой ошибки поля.
     */
    #[Test]
    public function bailStopsAfterFirstError(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [
                new BailValidation(),
                new FilledValidation(),
                new StringValidation()->min(2),
            ],
        ];

        $result = $validator->validate(['name' => ''], $rules);

        self::assertCount(1, $result->errorBag()->get('name'));
    }

    /**
     * Проверяет правило any_of.
     */
    #[Test]
    public function anyOfAcceptsFirstPassingRule(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [
                new AnyOfValidation(new IntValidation(), new StringValidation()),
            ],
        ];

        $result = $validator->validate(['value' => 10], $rules);

        self::assertFalse($result->hasErrors());
        self::assertSame(['value' => 10], $result->all());
    }

    /**
     * Проверяет, что any_of возвращает ошибку, если все правила не прошли.
     */
    #[Test]
    public function anyOfReturnsErrorWhenAllFail(): void
    {
        $validator = new Validator();
        $rules     = [
            'value' => [
                new AnyOfValidation(new IntValidation(), new StringValidation()),
            ],
        ];

        $result = $validator->validate(['value' => []], $rules);

        self::assertSame(
            ['Поле value не соответствует ни одному из допустимых правил.'],
            $result->errorBag()->get('value'),
        );
    }

    /**
     * Проверяет правило prohibited.
     */
    #[Test]
    public function prohibitedRejectsPresence(): void
    {
        $validator = new Validator();
        $rules     = [
            'secret' => [new ProhibitedValidation()],
        ];

        $result = $validator->validate(['secret' => 'x'], $rules);

        self::assertSame(['Поле secret запрещено.'], $result->errorBag()->get('secret'));
    }

    /**
     * Проверяет правило prohibited_if.
     */
    #[Test]
    public function prohibitedIfRejectsPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'secret' => [new ProhibitedValidation()->prohibitedIf('status', 'locked')],
        ];

        $result = $validator->validate(['status' => 'locked', 'secret' => 'x'], $rules);

        self::assertSame(
            ['Поле secret запрещено, если status равно ["locked"].'],
            $result->errorBag()->get('secret'),
        );
    }

    /**
     * Проверяет правило prohibited_unless.
     */
    #[Test]
    public function prohibitedUnlessRejectsPresenceWhenConditionMatches(): void
    {
        $validator = new Validator();
        $rules     = [
            'secret' => [new ProhibitedValidation()->prohibitedUnless('status', 'active')],
        ];

        $result = $validator->validate(['status' => 'inactive', 'secret' => 'x'], $rules);

        self::assertSame(
            ['Поле secret запрещено, если status не равно ["active"].'],
            $result->errorBag()->get('secret'),
        );
    }

    /**
     * Проверяет правило prohibited_if_accepted.
     */
    #[Test]
    public function prohibitedIfAcceptedRejectsPresenceWhenAccepted(): void
    {
        $validator = new Validator();
        $rules     = [
            'secret' => [new ProhibitedValidation()->prohibitedIfAccepted('terms')],
        ];

        $result = $validator->validate(['terms' => 'yes', 'secret' => 'x'], $rules);

        self::assertSame(
            ['Поле secret запрещено, если terms принято.'],
            $result->errorBag()->get('secret'),
        );
    }

    /**
     * Проверяет правило prohibited_if_declined.
     */
    #[Test]
    public function prohibitedIfDeclinedRejectsPresenceWhenDeclined(): void
    {
        $validator = new Validator();
        $rules     = [
            'secret' => [new ProhibitedValidation()->prohibitedIfDeclined('terms')],
        ];

        $result = $validator->validate(['terms' => 'no', 'secret' => 'x'], $rules);

        self::assertSame(
            ['Поле secret запрещено, если terms отклонено.'],
            $result->errorBag()->get('secret'),
        );
    }

    /**
     * Проверяет правило prohibits.
     */
    #[Test]
    public function prohibitsRejectsPresenceOfOtherFields(): void
    {
        $validator = new Validator();
        $rules     = [
            'role' => [new ProhibitsValidation('admin')],
        ];

        $result = $validator->validate(['role' => 'user', 'admin' => true], $rules);

        self::assertSame(
            ['Поле role запрещает наличие полей admin.'],
            $result->errorBag()->get('role'),
        );
    }

    /**
     * Проверяет правило exclude_with_all.
     */
    #[Test]
    public function excludeWithAllSkipsWhenAllFieldsPresent(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [
                new ExcludeValidation()->excludeWithAll('a', 'b'),
                new StringValidation()->min(3),
            ],
        ];

        $result = $validator->validate(['name' => 'a', 'a' => 1, 'b' => 2], $rules);

        self::assertFalse($result->hasErrors());
        self::assertSame([], $result->all());
    }

    /**
     * Проверяет правило exclude_without_all.
     */
    #[Test]
    public function excludeWithoutAllSkipsWhenAllFieldsMissing(): void
    {
        $validator = new Validator();
        $rules     = [
            'name' => [
                new ExcludeValidation()->excludeWithoutAll('a', 'b'),
                new StringValidation()->min(3),
            ],
        ];

        $result = $validator->validate(['name' => 'a'], $rules);

        self::assertFalse($result->hasErrors());
        self::assertSame([], $result->all());
    }

    /**
     * Проверяет, что управляющие правила запрещают required и nullable.
     */
    #[Test]
    public function controlRulesRejectRequiredAndNullable(): void
    {
        $this->assertThrows(fn () => new ExcludeValidation()->required());
        $this->assertThrows(fn () => new PresentValidation()->nullable());
        $this->assertThrows(fn () => new MissingValidation()->requiredIf(fn () => true));
        $this->assertThrows(fn () => new FilledValidation()->nullable());
        $this->assertThrows(fn () => new ProhibitedValidation()->required());
        $this->assertThrows(fn () => new BailValidation()->required());
    }

    private function assertThrows(callable $fn): void
    {
        try {
            $fn();
        } catch (LogicException $e) {
            self::assertStringContainsString('не поддерживается', $e->getMessage());

            return;
        }

        self::fail('Ожидалось исключение LogicException.');
    }
}
