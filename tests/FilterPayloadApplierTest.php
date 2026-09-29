<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use InvalidArgumentException;
use LogicException;
use PhpSoftBox\Filter\BooleanFilter;
use PhpSoftBox\Filter\DefaultFilter;
use PhpSoftBox\Filter\IntegerFilter;
use PhpSoftBox\Filter\TrimFilter;
use PhpSoftBox\Validator\Exception\FilterPayloadException;
use PhpSoftBox\Validator\Support\FilterPayloadApplier;
use PhpSoftBox\Validator\Support\FilterPayloadResult;
use PhpSoftBox\Validator\Tests\Fixtures\UppercasePayloadFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function is_numeric;
use function strtolower;
use function strtoupper;
use function trim;

#[CoversClass(FilterPayloadApplier::class)]
#[CoversClass(FilterPayloadResult::class)]
#[CoversMethod(FilterPayloadApplier::class, 'apply')]
final class FilterPayloadApplierTest extends TestCase
{
    /**
     * Проверяет применение фильтров по обычным путям и цепочкам фильтров.
     */
    #[Test]
    public function appliesFiltersForExactPathsAndChains(): void
    {
        $applier = new FilterPayloadApplier();

        $result = $applier->apply(
            payload: [
                'user' => [
                    'name' => '  John DOE  ',
                    'role' => 'ADMIN',
                ],
                'meta' => [
                    'city' => 'Moscow',
                ],
            ],
            filters: [
                'user.name' => [
                    static fn (mixed $value): string => trim((string) $value),
                    static fn (mixed $value): string => strtolower((string) $value),
                ],
                'user.role' => static fn (mixed $value): string => strtolower((string) $value),
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame('john doe', $result->payload['user']['name']);
        self::assertSame('admin', $result->payload['user']['role']);
        self::assertSame('Moscow', $result->payload['meta']['city']);
    }

    /**
     * Проверяет wildcard-фильтрацию и пропуск несуществующих совпадений.
     */
    #[Test]
    public function appliesFiltersForWildcardPaths(): void
    {
        $applier = new FilterPayloadApplier();

        $result = $applier->apply(
            payload: [
                'items' => [
                    'first'  => ['name' => ' a '],
                    'second' => ['name' => 'b '],
                    'third'  => ['name' => null],
                    'skip'   => ['title' => 'skip'],
                ],
            ],
            filters: [
                'items.*.name' => static fn (mixed $value): string => strtoupper(trim((string) $value)),
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame('A', $result->payload['items']['first']['name']);
        self::assertSame('B', $result->payload['items']['second']['name']);
        self::assertSame('', $result->payload['items']['third']['name']);
        self::assertArrayNotHasKey('name', $result->payload['items']['skip']);
        self::assertSame('skip', $result->payload['items']['skip']['title']);
    }

    /**
     * Проверяет сбор ошибок фильтрации и сохранение исходных значений для ошибочных путей.
     */
    #[Test]
    public function collectsErrorsAndKeepsOriginalValueForFailedPaths(): void
    {
        $applier = new FilterPayloadApplier();

        $result = $applier->apply(
            payload: [
                'prices' => [
                    'ok'  => '10',
                    'bad' => 'oops',
                ],
            ],
            filters: [
                ''         => static fn (mixed $value): mixed => $value,
                'prices.*' => static function (mixed $value): int {
                    if (!is_numeric($value)) {
                        throw new FilterPayloadException('price must be numeric');
                    }

                    return (int) $value;
                },
            ],
        );

        self::assertSame(10, $result->payload['prices']['ok']);
        self::assertSame('oops', $result->payload['prices']['bad']);
        self::assertSame(
            ['prices.bad' => ['price must be numeric']],
            $result->errors,
        );
    }

    /**
     * Проверяет обратную совместимость: InvalidArgumentException из фильтра конвертируется в filter-ошибку.
     */
    #[Test]
    public function convertsInvalidArgumentExceptionToFilterError(): void
    {
        $applier = new FilterPayloadApplier();

        $result = $applier->apply(
            payload: ['qty' => 'x'],
            filters: [
                'qty' => static function (mixed $value): int {
                    if (!is_numeric($value)) {
                        throw new InvalidArgumentException('qty must be numeric');
                    }

                    return (int) $value;
                },
            ],
        );

        self::assertSame('x', $result->payload['qty']);
        self::assertSame(['qty' => ['qty must be numeric']], $result->errors);
    }

    /**
     * Проверим, что умолчание фильтра для непереданного поля попадает в payload.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function missingExactPathReceivesFilterDefault(): void
    {
        $result = new FilterPayloadApplier()->apply(
            payload: ['user' => ['name' => 'Alice']],
            filters: [
                'status'     => static fn (mixed $value): mixed => $value ?? 'all',
                'user.phone' => [
                    static fn (mixed $value): mixed => $value,
                    static fn (mixed $value): mixed => $value ?? 'unknown',
                ],
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame(['user' => ['name' => 'Alice', 'phone' => 'unknown'], 'status' => 'all'], $result->payload);
    }

    /**
     * Проверим умолчания фильтров phpsoftbox/filter для непереданных полей: `DefaultFilter`, `IntegerFilter`,
     * `BooleanFilter` подставляют значение, `TrimFilter` без умолчания поле не создаёт.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function missingExactPathsReceiveFilterPackageDefaults(): void
    {
        $result = new FilterPayloadApplier()->apply(
            payload: ['search' => ' box '],
            filters: [
                'search'  => [new TrimFilter()],
                'status'  => [new DefaultFilter('all')],
                'page'    => [new IntegerFilter(1)],
                'only_my' => [new BooleanFilter(false)],
                'comment' => [new TrimFilter()],
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame(
            ['search' => 'box', 'status' => 'all', 'page' => 1, 'only_my' => false],
            $result->payload,
        );
    }

    /**
     * Проверим, что непереданное поле не создаётся, если фильтр вернул null или '': необязательные правила видят
     * «не передано», а не пустое значение.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function missingExactPathStaysMissingWhenFilterReturnsNull(): void
    {
        $result = new FilterPayloadApplier()->apply(
            payload: ['user' => ['name' => 'Alice']],
            filters: [
                'user.phone' => static fn (mixed $value): mixed => $value,
                'user.email' => static fn (mixed $value): string => (string) $value,
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame(['user' => ['name' => 'Alice']], $result->payload);
    }

    /**
     * Проверим, что ошибка фильтра на непереданном поле не становится ошибкой валидации: поле остаётся непереданным.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function missingExactPathIgnoresFilterError(): void
    {
        $result = new FilterPayloadApplier()->apply(
            payload: [],
            filters: [
                'qty' => static function (mixed $value): never {
                    throw new InvalidArgumentException('qty must be numeric');
                },
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame([], $result->payload);
    }

    /**
     * Проверяет DTO-объект результата фильтрации.
     */
    #[Test]
    public function filterPayloadResultStoresPayloadAndErrors(): void
    {
        $result = new FilterPayloadResult(
            payload: ['a' => 1],
            errors: ['field' => ['broken']],
        );

        self::assertSame(['a' => 1], $result->payload);
        self::assertSame(['field' => ['broken']], $result->errors);

        $withoutErrors = new FilterPayloadResult(['ok' => true]);

        self::assertSame([], $withoutErrors->errors);
    }

    /**
     * Проверим, что invokable-объект принимается как фильтр и в списке, и отдельно.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function acceptsInvokableObjects(): void
    {
        $result = new FilterPayloadApplier()->apply(
            payload: ['a' => 'x', 'b' => 'y'],
            filters: ['a' => new UppercasePayloadFilter(), 'b' => [new UppercasePayloadFilter()]],
        );

        self::assertSame(['a' => 'X', 'b' => 'Y'], $result->payload);
    }

    /**
     * Проверим, что строка-функция не исполняется как фильтр.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function rejectsFunctionName(): void
    {
        $this->expectException(LogicException::class);

        new FilterPayloadApplier()->apply(payload: ['a' => ' x '], filters: ['a' => 'trim']);
    }

    /**
     * Проверим, что массив `[$object, 'method']` считается списком фильтров и отклоняется, а не вызывается.
     *
     * @see FilterPayloadApplier::apply()
     */
    #[Test]
    public function rejectsCallableArray(): void
    {
        $this->expectException(LogicException::class);

        new FilterPayloadApplier()->apply(
            payload: ['a' => 'x'],
            filters: ['a' => [new UppercasePayloadFilter(), '__invoke']],
        );
    }
}
