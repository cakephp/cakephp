<?php
declare(strict_types=1);

/**
 * ValidationRule.
 *
 * Provides the Model validation logic.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Validation;

use Closure;
use ReflectionFunction;

/**
 * ValidationRule object. Represents a validation method, error message and
 * rules for applying such method to a field.
 */
class ValidationRule
{
    /**
     * Constructor
     */
    public function __construct(
        public readonly Closure $callable,
        public readonly ?string $name = null,
        public readonly ?string $message = null,
        public readonly Closure|string|null $on = null,
        public readonly bool $last = false,
        public readonly array $pass = [],
    ) {
    }

    /**
     * Dispatches the validation rule to the given validator method and returns
     * a boolean indicating whether the rule passed or not. If a string is returned
     * it is assumed that the rule failed and the error message was given as a result.
     *
     * @param mixed $value The data to validate
     * be passed as the last argument for the validation method
     * @param array<string, mixed> $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - field: The name of the field that is being processed
     * @return array|string|bool
     * @throws \InvalidArgumentException when the supplied rule is not a valid
     * callable for the configured scope
     */
    public function process(mixed $value, array $context = []): array|string|bool
    {
        $context += ['data' => [], 'newRecord' => true];

        if ($this->skip($context)) {
            return true;
        }

        $args = [$value];
        if ($this->pass) {
            $args = array_merge([$value], array_values($this->pass));
        }

        $params = new ReflectionFunction($this->callable)->getParameters();
        $lastParam = array_pop($params);
        if ($lastParam && $lastParam->getName() === 'context') {
            $args['context'] = $context;
        }

        $result = ($this->callable)(...$args);

        if ($result === false) {
            return $this->message ?: false;
        }

        return $result;
    }

    /**
     * Checks if the validation rule should be skipped
     *
     * @param array<string, mixed> $context A key value list of data that could be used as context
     * during validation. Recognized keys are:
     * - newRecord: (boolean) whether the data to be validated belongs to a
     *   new record
     * - data: The full data that was passed to the validation process
     * - providers associative array with objects or class names that will
     *   be passed as the last argument for the validation method
     * @return bool True if the ValidationRule should be skipped
     */
    protected function skip(array $context): bool
    {
        if (is_string($this->on)) {
            $newRecord = $context['newRecord'];

            return ($this->on === Validator::WHEN_CREATE && !$newRecord)
                || ($this->on === Validator::WHEN_UPDATE && $newRecord);
        }

        if ($this->on !== null) {
            $function = $this->on;

            return !$function($context);
        }

        return false;
    }
}
