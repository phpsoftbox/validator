<?php

declare(strict_types=1);

namespace PhpSoftBox\Validator;

use InvalidArgumentException;
use PhpSoftBox\Validator\Exception\RuleExecutorNotFoundException;
use PhpSoftBox\Validator\Rule\AbstractRule;
use PhpSoftBox\Validator\Rule\Executor\RuleExecutorRegistryInterface;
use PhpSoftBox\Validator\Rule\RuleSpecificationInterface;
use PhpSoftBox\Validator\Rule\ValidationRuleInterface;
use PhpSoftBox\Validator\Support\DataPath;
use PhpSoftBox\Validator\Support\MessageFormatter;

use function array_key_exists;
use function array_merge;
use function is_array;
use function is_string;

final class Validator implements ValidatorInterface
{
    public function __construct(
        private readonly ?RuleExecutorRegistryInterface $ruleExecutors = null,
    ) {
    }

    public function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = [],
        ?ValidationOptions $options = null,
        mixed $context = null,
    ): ValidationResult {
        $options ??= new ValidationOptions();

        $errors   = [];
        $filtered = [];

        foreach ($rules as $fieldPattern => $ruleSet) {
            $ruleList = $this->normalizeRules($ruleSet);
            $useBail  = $this->hasBail($ruleList);
            $values   = DataPath::extract($data, (string) $fieldPattern);

            foreach ($values as $pathValue) {
                $field   = $pathValue->path;
                $value   = $pathValue->value;
                $present = $pathValue->present;

                if ($this->shouldExclude($ruleList, $data, $context, $field)) {
                    DataPath::forget($filtered, $field);
                    continue;
                }

                $requiredViolation = $this->requiredViolation($ruleList, $data, $context, $field);

                if (!$present) {
                    if ($requiredViolation !== null) {
                        $this->addError(
                            $errors,
                            $field,
                            $requiredViolation->rule,
                            $value,
                            $requiredViolation->params,
                            $messages,
                            $attributes,
                            $ruleList,
                        );
                        if ($options->stopMode === ValidationStopMode::FIRST_ERROR) {
                            return new ValidationResult($errors, $filtered);
                        }
                        if ($useBail) {
                            continue;
                        }
                    }
                    continue;
                }

                if ($this->isEmpty($value)) {
                    if ($requiredViolation !== null) {
                        $this->addError(
                            $errors,
                            $field,
                            $requiredViolation->rule,
                            $value,
                            $requiredViolation->params,
                            $messages,
                            $attributes,
                            $ruleList,
                        );
                        if ($options->stopMode === ValidationStopMode::FIRST_ERROR) {
                            return new ValidationResult($errors, $filtered);
                        }
                        if ($useBail) {
                            continue;
                        }
                    } elseif ($this->isNullable($ruleList)) {
                        continue;
                    }
                }

                foreach ($ruleList as $rule) {
                    if ($rule instanceof Rule\BailValidation || $rule instanceof Rule\ExcludeValidation) {
                        continue;
                    }

                    $violations = $this->executeRule($rule, $value, $field, $present, $data, $context);
                    foreach ($violations as $violation) {
                        $this->addError(
                            $errors,
                            $field,
                            $violation->rule,
                            $value,
                            $violation->params,
                            $messages,
                            $attributes,
                            $ruleList,
                            $rule,
                        );

                        if ($options->stopMode === ValidationStopMode::FIRST_ERROR) {
                            return new ValidationResult($errors, $filtered);
                        }
                        if ($options->stopMode === ValidationStopMode::FIRST_PER_FIELD) {
                            break 2;
                        }
                        if ($useBail) {
                            break 2;
                        }
                    }
                }

                if (!array_key_exists($field, $errors)) {
                    DataPath::set($filtered, $field, $value);
                }
            }
        }

        return new ValidationResult($errors, $filtered);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function executeRule(
        ValidationRuleInterface $rule,
        mixed $value,
        string $field,
        bool $present,
        array $data,
        mixed $context,
    ): array {
        if ($rule instanceof AbstractRule) {
            $rule->setRuntimeState($data, $context);
        }

        if ($rule instanceof RuleSpecificationInterface) {
            if ($this->ruleExecutors === null) {
                throw new RuleExecutorNotFoundException(
                    'Rule executor registry is not configured for specification rules.',
                );
            }

            $executor = $this->ruleExecutors->resolve($rule);

            return $executor->validate($rule, $value, $field, $present, $data);
        }

        return $rule->validate($value, $field, $present, $data);
    }

    /**
     * @param array<string, mixed> $ruleSet
     * @return list<ValidationRuleInterface>
     */
    private function normalizeRules(mixed $ruleSet): array
    {
        if ($ruleSet instanceof ValidationRuleInterface) {
            return [$ruleSet];
        }

        if (!is_array($ruleSet)) {
            throw new InvalidArgumentException('Validation rules must be array or ValidationRuleInterface.');
        }

        $rules = [];
        foreach ($ruleSet as $rule) {
            if (!$rule instanceof ValidationRuleInterface) {
                throw new InvalidArgumentException('Each rule must implement ValidationRuleInterface.');
            }
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @param list<ValidationRuleInterface> $rules
     */
    private function requiredViolation(array $rules, array $data, mixed $context, string $field): ?ValidationViolation
    {
        foreach ($rules as $rule) {
            $violation = $rule->requiredViolation($data, $context, $field);
            if ($violation !== null) {
                return $violation;
            }
        }

        return null;
    }

    /**
     * @param list<ValidationRuleInterface> $rules
     */
    private function shouldExclude(array $rules, array $data, mixed $context, string $field): bool
    {
        foreach ($rules as $rule) {
            if ($rule->shouldExclude($data, $context, $field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ValidationRuleInterface> $rules
     */
    private function hasBail(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Rule\BailValidation) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ValidationRuleInterface> $rules
     */
    private function isNullable(array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($rule->isNullable()) {
                return true;
            }
        }

        return false;
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_array($value) && $value === [];
    }

    /**
     * @param array<string, list<ValidationError>> $errors
     * @param array<string, mixed> $messages
     * @param array<string, string> $attributes
     * @param list<ValidationRuleInterface> $rules
     */
    private function addError(
        array &$errors,
        string $field,
        string $rule,
        mixed $value,
        array $params,
        array $messages,
        array $attributes,
        array $rules,
        ?ValidationRuleInterface $currentRule = null,
    ): void {
        $params = array_merge([
            'field' => $this->resolveAttribute($field, $attributes),
            'value' => $value,
            'rule'  => $rule,
        ], $params);

        $template = $this->resolveMessage($field, $rule, $messages, $rules, $currentRule);
        $message  = MessageFormatter::format($template, $params);

        $errors[$field] ??= [];
        $errors[$field][] = new ValidationError($field, $rule, $message, $params);
    }

    /**
     * @param array<string, mixed> $messages
     * @param list<ValidationRuleInterface> $rules
     */
    private function resolveMessage(
        string $field,
        string $rule,
        array $messages,
        array $rules,
        ?ValidationRuleInterface $currentRule = null,
    ): string {
        $customMessage = $this->resolveCustomMessage($field, $rule, $messages);
        if ($customMessage !== null) {
            return $customMessage;
        }

        if ($currentRule !== null) {
            $ruleMessages = $currentRule->customMessages();
            if (array_key_exists($rule, $ruleMessages)) {
                return $ruleMessages[$rule];
            }
        } else {
            foreach ($rules as $ruleObj) {
                $ruleMessages = $ruleObj->customMessages();
                if (array_key_exists($rule, $ruleMessages)) {
                    return $ruleMessages[$rule];
                }
            }
        }

        foreach ($rules as $ruleObj) {
            $messagesMap = $ruleObj->messages();
            if (array_key_exists($rule, $messagesMap)) {
                return $messagesMap[$rule];
            }
        }

        if ($rule === ValidationEnum::REQUIRED->value) {
            return 'Поле {field} обязательно.';
        }
        if ($rule === ValidationEnum::REQUIRED_IF->value) {
            return 'Поле {field} обязательно при условии {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_IF_ACCEPTED->value) {
            return 'Поле {field} обязательно при принятии поля {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_IF_DECLINED->value) {
            return 'Поле {field} обязательно при отклонении поля {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_UNLESS->value) {
            return 'Поле {field} обязательно, если {other} не равно {values}.';
        }
        if ($rule === ValidationEnum::REQUIRED_WITH->value) {
            return 'Поле {field} обязательно при наличии полей {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_WITH_ALL->value) {
            return 'Поле {field} обязательно при наличии всех полей {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_WITHOUT->value) {
            return 'Поле {field} обязательно при отсутствии полей {other}.';
        }
        if ($rule === ValidationEnum::REQUIRED_WITHOUT_ALL->value) {
            return 'Поле {field} обязательно при отсутствии всех полей {other}.';
        }

        return 'Поле {field} содержит неверное значение.';
    }

    /**
     * @param array<string, mixed> $messages
     */
    private function resolveCustomMessage(string $field, string $rule, array $messages): ?string
    {
        $direct = $messages[$field] ?? null;
        if (is_array($direct) && array_key_exists($rule, $direct) && is_string($direct[$rule])) {
            return $direct[$rule];
        }

        foreach ($messages as $pattern => $rules) {
            if (!is_string($pattern) || !is_array($rules)) {
                continue;
            }
            if (!DataPath::matches($pattern, $field)) {
                continue;
            }
            if (array_key_exists($rule, $rules) && is_string($rules[$rule])) {
                return $rules[$rule];
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $attributes
     */
    private function resolveAttribute(string $field, array $attributes): string
    {
        if (array_key_exists($field, $attributes)) {
            return $attributes[$field];
        }

        foreach ($attributes as $pattern => $label) {
            if (DataPath::matches($pattern, $field)) {
                return $label;
            }
        }

        return $field;
    }
}
