<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator\Rule;

/**
 * Маркерный контракт декларативного правила (specification).
 *
 * На этапе миграции сохраняет совместимость со старым правилом, но
 * используется как отдельный тип для executor-слоя.
 */
interface RuleSpecificationInterface extends ValidationRuleInterface
{
}
