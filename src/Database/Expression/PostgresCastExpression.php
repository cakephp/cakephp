<?php
declare(strict_types=1);

namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;
use Closure;

/**
 * An expression for a postgres cast operation using (value::type)
 * Primarily used by the Postgres driver when converting expressions into
 * the postgres dialect.
 *
 * @internal
 */
class PostgresCastExpression implements ExpressionInterface
{
    /**
     * The type to cast to
     *
     * @var string $_type
     */
    protected string $type;

    /**
     * The expression to be cast
     *
     * @var string $expression
     */
    protected ExpressionInterface|string|array $expression;

    /**
     * Constructor
     *
     * If `expression` is a string, it is assumed to be a column name, which is **unsafe** for user controlled
     * inputs. All other types for `expression` will be use bound parameters.
     *
     * @param \Cake\Database\ExpressionInterface|string|array $expression The expression or value to be cast.
     * @param string $type The cast type to use.
     */
    public function __construct(ExpressionInterface|string|array $expression, string $type)
    {
        $this->expression = $expression;
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function traverse(Closure $callback)
    {
        if ($this->expression instanceof ExpressionInterface) {
            $callback($this->expression);
            $this->expression->traverse($callback);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function sql(ValueBinder $binder): string
    {
        $expression = $this->expression;

        if ($expression instanceof Query) {
            $expression = sprintf('(%s)', $expression->sql($binder));
        } elseif ($expression instanceof ExpressionInterface) {
            $expression = $expression->sql($binder);
        } elseif (is_array($expression)) {
            $p = $binder->placeholder('param');
            $binder->bind($p, $expression['value'], $expression['type']);
            $expression = $p;
        }

        return sprintf('%s::%s', $expression, $this->type);
    }
}
