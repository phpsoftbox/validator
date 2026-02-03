<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Support;

use InvalidArgumentException;
use PhpSoftBox\Collection\Collection;
use PhpSoftBox\Validator\Exception\FilterPayloadException;

use function is_array;
use function is_string;
use function str_contains;

final class FilterPayloadApplier
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, callable(mixed): mixed|list<callable(mixed): mixed>> $filters
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
     * @param callable(mixed): mixed|list<callable(mixed): mixed> $filters
     */
    private function run(mixed $value, mixed $filters): mixed
    {
        try {
            if (is_array($filters)) {
                foreach ($filters as $item) {
                    $value = $item($value);
                }

                return $value;
            }

            return $filters($value);
        } catch (InvalidArgumentException $exception) {
            throw new FilterPayloadException(
                message: $exception->getMessage(),
                previous: $exception,
            );
        }
    }
}
