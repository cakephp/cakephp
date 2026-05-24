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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\TypedResultInterface;
use Cake\Database\TypedResultTrait;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Represents a JSON path expression with optional clauses for each json function.
 */
class JsonPathExpression implements ExpressionInterface, TypedResultInterface
{
    use TypedResultTrait;

    public const BEHAVIOR_NULL = 'NULL';
    public const BEHAVIOR_ERROR = 'ERROR';
    public const BEHAVIOR_DEFAULT = 'DEFAULT';

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var array<string, array{value: mixed, type: string|null}>
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
     * Constructor.
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
     * Not all database engines support all clauses. Check for support
     * before using.
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
     * Not all database engines support all clauses. Check for support
     * before using.
     *
     * @param array<string, string|int|float|bool> $passing Mapping of variable name to value.
     * @param array<string, string|int> $types Optional mapping of variable name to binding type.
     * @return $this
     */
    public function passing(array $passing, array $types = [])
    {
        foreach ($passing as $name => $value) {
            $type = $types[$name] ?? null;
            if ($type === null) {
                $type = match (true) {
                    is_string($value) => 'string',
                    is_int($value) => 'integer',
                    is_float($value) => 'float',
                    is_bool($value) => 'boolean',
                    default => null,
                };
            }

            $this->passing[$name] = ['value' => $value, 'type' => $type];
        }

        return $this;
    }

    /**
     * Sets the ON EMPTY clause.
     *
     * Not all database engines support all clauses. Check for support
     * before using.
     *
     * @param self::BEHAVIOR_* $behavior The behavior on empty (NULL, ERROR, or DEFAULT).
     * @param mixed $value The default value if behavior is DEFAULT.
     * @return $this
     */
    public function onEmpty(string $behavior, mixed $value = null)
    {
        $this->onEmpty = ['behavior' => $behavior, 'value' => $value];

        return $this;
    }

    /**
     * Sets the ON ERROR clause.
     *
     * Not all database engines support all clauses. Check for support
     * before using.
     *
     * @param self::BEHAVIOR_* $behavior The behavior on error (NULL, ERROR, or DEFAULT).
     * @param mixed $value The default value if behavior is DEFAULT.
     * @return $this
     */
    public function onError(string $behavior, mixed $value = null)
    {
        $this->onError = ['behavior' => $behavior, 'value' => $value];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $sql = "'{$this->path}'";

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
            $sql .= ' ' . $this->behaviorSql($this->onEmpty, 'EMPTY', $binder);
        }

        if ($this->onError !== null) {
            $sql .= ' ' . $this->behaviorSql($this->onError, 'ERROR', $binder);
        }

        return $sql;
    }

    /**
     * Generates the SQL for ON EMPTY or ON ERROR clauses.
     *
     * @param array $clause The clause configuration.
     * @param string $type The type of clause (EMPTY or ERROR).
     * @param \Cake\Database\ValueBinder $binder The value binder.
     * @return string
     */
    protected function behaviorSql(array $clause, string $type, ValueBinder $binder): string
    {
        $behavior = strtoupper($clause['behavior']);
        if ($behavior === self::BEHAVIOR_DEFAULT) {
            $value = $clause['value'];
            if ($value instanceof ExpressionInterface) {
                $value = $value->sql($binder);
            } elseif (is_string($value)) {
                $value = sprintf("'%s'", str_replace("'", "''", $value));
            } elseif (is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } elseif ($value === null) {
                $value = 'NULL';
            }

            // DEFAULT clause require a literal value
            return sprintf('DEFAULT %s ON %s', $value, $type);
        }

        return sprintf('%s ON %s', $behavior, $type);
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

        if ($this->onEmpty !== null && $this->onEmpty['value'] instanceof ExpressionInterface) {
            $callback($this->onEmpty['value']);
            $this->onEmpty['value']->traverse($callback);
        }

        if ($this->onError !== null && $this->onError['value'] instanceof ExpressionInterface) {
            $callback($this->onError['value']);
            $this->onError['value']->traverse($callback);
        }

        return $this;
    }
}
