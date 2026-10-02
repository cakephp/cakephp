<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypedResultTrait;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;

/**
 * Represents a JSON path expression with optional clauses for each json function.
 */
class JsonPathExpression implements ExpressionInterface, TypedResultInterface
{
    use TypedResultTrait;
    use TypeInferenceTrait;

    public const BEHAVIOR_NULL = 'NULL';
    public const BEHAVIOR_ERROR = 'ERROR';
    public const BEHAVIOR_DEFAULT = 'DEFAULT';

    protected const ALLOWED_BEHAVIORS = [
        self::BEHAVIOR_NULL,
        self::BEHAVIOR_ERROR,
        self::BEHAVIOR_DEFAULT,
    ];

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var array<string, array{value: mixed, type: string|int|null}>
     */
    protected array $passing = [];

    /**
     * @var string|null
     */
    protected ?string $returning = null;

    /**
     * @var array{behavior: string, value: mixed}|null
     */
    protected ?array $onEmpty = null;

    /**
     * @var array{behavior: string, value: mixed}|null
     */
    protected ?array $onError = null;

    /**
     * Constructs with base json path string.
     *
     * @param string $path The json path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Sets the RETURNING clause.
     *
     * Caution: returning types are not sanitized before use.
     *
     * Not all database engines support every clauses. Check for support before using.
     *
     * @param string $type The sql data type to return
     * @return $this
     */
    public function returning(string $type)
    {
        $this->returning = $type;

        return $this;
    }

    /**
     * Sets the PASSING clause.
     *
     * Caution: variables names are not sanitized before use.
     *
     * Not all database engines support every clauses. Check for support before using.
     *
     * @param array<string, string|int|float|bool> $passing Mapping of variable name to value.
     * @param array<string, string|int> $types Optional mapping of variable name to binding type.
     * @return $this
     */
    public function passing(array $passing, array $types = [])
    {
        foreach ($passing as $name => $value) {
            $type = $types[$name] ?? $this->inferType($value);

            $this->passing[$name] = ['value' => $value, 'type' => $type];
        }

        return $this;
    }

    /**
     * Sets the ON EMPTY clause.
     *
     * Not all database engines support every clauses. Check for support before using.
     *
     * @param self::BEHAVIOR_* $behavior The behavior on empty (NULL, ERROR, or DEFAULT).
     * @param mixed $value The value if behavior is DEFAULT.
     * @return $this
     */
    public function onEmpty(string $behavior, mixed $value = null)
    {
        $this->onEmpty = $this->buildBehavior($behavior, $value);

        return $this;
    }

    /**
     * Sets the ON ERROR clause.
     *
     * Not all database engines support every clauses. Check for support before using.
     *
     * @param self::BEHAVIOR_* $behavior The behavior on error (NULL, ERROR, or DEFAULT).
     * @param mixed $value The value if behavior is DEFAULT.
     * @return $this
     */
    public function onError(string $behavior, mixed $value = null)
    {
        $this->onError = $this->buildBehavior($behavior, $value);

        return $this;
    }

    /**
     * Validate and build behavior config.
     *
     * @param string $behavior The behavior name.
     * @param mixed $value The value if behavior is DEFAULT.
     * @return array{behavior: string, value: mixed}
     */
    protected function buildBehavior(string $behavior, mixed $value): array
    {
        $behavior = strtoupper($behavior);
        if (!in_array($behavior, self::ALLOWED_BEHAVIORS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported json path behavior `%s`. Allowed behaviors are: %s.',
                $behavior,
                implode(', ', self::ALLOWED_BEHAVIORS),
            ));
        }

        return ['behavior' => $behavior, 'value' => $value];
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $path = $binder->placeholder('param');
        $binder->bind($path, $this->path, 'string');
        $sql = $path;

        if ($this->passing) {
            $passing = [];
            foreach ($this->passing as $name => ['value' => $value, 'type' => $type]) {
                if ($value instanceof ExpressionInterface) {
                    $exprSql = $value->sql($binder);
                } else {
                    $placeholder = $binder->placeholder('param');
                    $binder->bind($placeholder, $value, $type);
                    $exprSql = $placeholder;
                }
                $passing[] = sprintf('%s AS %s', $exprSql, $name);
            }
            $sql .= ' PASSING ' . implode(', ', $passing);
        }

        if ($this->returning) {
            $sql .= ' RETURNING ' . $this->returning;
        }

        if ($this->onEmpty !== null) {
            $sql .= ' ' . $this->behaviorSql('ON EMPTY', $this->onEmpty, $binder);
        }

        if ($this->onError !== null) {
            $sql .= ' ' . $this->behaviorSql('ON ERROR', $this->onError, $binder);
        }

        return $sql;
    }

    /**
     * Generates the SQL for ON EMPTY or ON ERROR clauses.
     *
     * @param string $origin The behavior origin.
     * @param array $config The behavior config.
     * @param \Cake\Database\ValueBinder $binder The value binder.
     * @return string
     */
    protected function behaviorSql(string $origin, array $config, ValueBinder $binder): string
    {
        switch ($config['behavior']) {
            case self::BEHAVIOR_NULL:
                return sprintf('NULL %s', $origin);
            case self::BEHAVIOR_ERROR:
                return sprintf('ERROR %s', $origin);
            case self::BEHAVIOR_DEFAULT:
                $value = $config['value'];
                if ($value instanceof ExpressionInterface) {
                    $value = $value->sql($binder);
                } elseif (is_string($value)) {
                    $value = sprintf("'%s'", str_replace("'", "''", $value));
                } elseif (is_bool($value)) {
                    $value = $value ? 'TRUE' : 'FALSE';
                } elseif ($value === null) {
                    $value = 'NULL';
                }

                // DEFAULT behavior require a literal value
                return sprintf('DEFAULT %s %s', $value, $origin);
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback)
    {
        foreach ($this->passing as ['value' => $value]) {
            if ($value instanceof ExpressionInterface) {
                $callback($value);
                $value->traverse($callback);
            }
        }

        foreach ([$this->onEmpty, $this->onError] as $clause) {
            if ($clause !== null && $clause['value'] instanceof ExpressionInterface) {
                $callback($clause['value']);
                $clause['value']->traverse($callback);
            }
        }

        return $this;
    }
}
