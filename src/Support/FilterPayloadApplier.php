<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

use Closure;
use InvalidArgumentException;
use LogicException;
use PhpSoftBox\Collection\Collection;
use PhpSoftBox\Validator\Exception\FilterPayloadException;

use function array_is_list;
use function get_debug_type;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function str_contains;

/**
 * Применяет фильтры к payload по dot-путям и wildcard.
 *
 * Для отсутствующего точного пути фильтры вызываются с null: результат-умолчание записывается в payload, null и ''
 * — поле не создаётся. Wildcard-пути фильтруют только существующие элементы.
 *
 * Фильтр — `Closure` или invokable-объект (например, `FilterInterface`), либо список таких фильтров. Массив всегда
 * считается списком: `[$object, 'method']` и строки-функции не принимаются (`LogicException`), чтобы значение не
 * исполнялось как произвольный callable.
 */
final class FilterPayloadApplier
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, (Closure(mixed): mixed)|object|list<(Closure(mixed): mixed)|object>> $filters
     */
    public function apply(array $payload, array $filters): FilterPayloadResult
    {
        $patch  = [];
        $errors = [];

        foreach ($filters as $path => $filter) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            if (str_contains($path, '*')) {
                $matches = DataPath::extract($payload, $path);

                foreach ($matches as $match) {
                    if (!$match->present) {
                        continue;
                    }

                    try {
                        $value = $this->run($match->value, $filter);
                    } catch (FilterPayloadException $exception) {
                        $errors[$match->path] ??= [];
                        $errors[$match->path][] = $exception->getMessage();
                        continue;
                    }

                    DataPath::set($patch, $match->path, $value);
                }

                continue;
            }

            if (!DataPath::has($payload, $path)) {
                // Отсутствующее поле: фильтры получают null. Поле создаётся, только если фильтр подставил умолчание
                // (`DefaultFilter('all')`, `IntegerFilter(1)`); null, '' (`TrimFilter` от null) и ошибка фильтра
                // оставляют поле непереданным — иначе необязательные правила получили бы пустое значение вместо
                // «не передано».
                try {
                    $value = $this->run(null, $filter);
                } catch (FilterPayloadException) {
                    continue;
                }

                if ($value !== null && $value !== '') {
                    DataPath::set($patch, $path, $value);
                }

                continue;
            }

            try {
                $value = $this->run(DataPath::get($payload, $path), $filter);
            } catch (FilterPayloadException $exception) {
                $errors[$path] ??= [];
                $errors[$path][] = $exception->getMessage();
                continue;
            }

            DataPath::set($patch, $path, $value);
        }

        $merged = Collection::from($payload)->merge($patch, ['recursive' => true])->all();

        return new FilterPayloadResult($merged, $errors);
    }

    /**
     * @param (Closure(mixed): mixed)|object|list<(Closure(mixed): mixed)|object> $filters
     */
    private function run(mixed $value, mixed $filters): mixed
    {
        $chain = is_array($filters) ? $filters : [$filters];
        if (!array_is_list($chain)) {
            throw new LogicException('Payload filters must be a list.');
        }

        try {
            foreach ($chain as $filter) {
                $value = $this->invoke($filter, $value);
            }

            return $value;
        } catch (InvalidArgumentException $exception) {
            throw new FilterPayloadException(
                message: $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    private function invoke(mixed $filter, mixed $value): mixed
    {
        if ($filter instanceof Closure || (is_object($filter) && is_callable($filter))) {
            return $filter($value);
        }

        throw new LogicException(
            'Payload filter must be a Closure or an invokable object, ' . get_debug_type($filter) . ' given.',
        );
    }
}
