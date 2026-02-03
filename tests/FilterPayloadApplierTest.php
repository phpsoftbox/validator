<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Tests;

use InvalidArgumentException;
use PhpSoftBox\Validator\Exception\FilterPayloadException;
use PhpSoftBox\Validator\Support\FilterPayloadApplier;
use PhpSoftBox\Validator\Support\FilterPayloadResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function is_numeric;
use function strtolower;
use function strtoupper;
use function trim;

#[CoversClass(FilterPayloadApplier::class)]
#[CoversClass(FilterPayloadResult::class)]
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
     * Проверяет, что для отсутствующего точечного пути фильтр получает null и может записать значение.
     */
    #[Test]
    public function appliesFilterForMissingExactPath(): void
    {
        $applier = new FilterPayloadApplier();

        $result = $applier->apply(
            payload: ['user' => ['name' => 'Alice']],
            filters: [
                'user.phone' => static fn (mixed $value): string => $value === null ? 'unknown' : (string) $value,
            ],
        );

        self::assertSame([], $result->errors);
        self::assertSame('unknown', $result->payload['user']['phone']);
        self::assertSame('Alice', $result->payload['user']['name']);
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
}
