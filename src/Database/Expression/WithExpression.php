<?php
declare(strict_types=1);

namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * An expression that represents a WITH clause.
 */
class WithExpression implements ExpressionInterface
{
    /**
     * Whether to use keywords.
     *
     * @var bool
     */
    protected $useKeywords = true;

    /**
     * The attached CTE's.
     *
     * @var \Cake\Database\Expression\CommonTableExpression[]
     */
    protected $expressions = [];

    /**
     * Enables/Disables keywords.
     *
     * @param bool $enable Whether to enable keywords. Defaults to `true`.
     * @return $this
     */
    public function enableKeywords(bool $enable = true)
    {
        $this->useKeywords = (bool)$enable;

        return $this;
    }

    /**
     * Disables keywords.
     *
     * @return $this
     */
    public function disableKeywords()
    {
        $this->useKeywords = false;

        return $this;
    }

    /**
     * Returns whether keywords are enabled.
     *
     * @return bool
     */
    public function isKeywordsEnabled(): bool
    {
        return $this->useKeywords;
    }

    /**
     * Returns the common table expressions (CTE).
     *
     * @return \Cake\Database\Expression\CommonTableExpression[]
     */
    public function getExpressions(): array
    {
        return \array_values($this->expressions);
    }

    /**
     * Sets the common table expressions (CTE) to use.
     *
     * This method will overwrite any existing CTE's.
     *
     * @param \Cake\Database\Expression\CommonTableExpression[] $expressions The CTE's to set.
     * @return $this
     * @throws \InvalidArgumentException In case any of the expressions is of an invalid type.
     */
    public function setExpressions(array $expressions)
    {
        $this->expressions = [];

        foreach ($expressions as $index => $expression) {
            if (!($expression instanceof CommonTableExpression)) {
                throw new InvalidArgumentException(sprintf(
                    'The `$expressions` argument must contain only instances of `%s`, `%s` given at index `%d`.',
                    CommonTableExpression::class,
                    getTypeName($expression),
                    $index
                ));
            }

            $this->addExpression($expression);
        }

        return $this;
    }

    /**
     * Constructor.
     *
     * @param \Cake\Database\Expression\CommonTableExpression[] $expressions The CTE's to add.
     * @throws \InvalidArgumentException In case any of the expressions is of an invalid type.
     */
    public function __construct($expressions = [])
    {
        $this->setExpressions($expressions);
    }

    /**
     * Adds a common table expression (CTE).
     *
     * @param \Cake\Database\Expression\CommonTableExpression $expression The CTE to add.
     * @return $this
     * @throws \InvalidArgumentException In case a CTE with the same name already exists.
     */
    public function addExpression(CommonTableExpression $expression)
    {
        $name = $expression->getName();
        if (isset($this->expressions[$name])) {
            throw new InvalidArgumentException(
                sprintf('A common table expression with the name `%s` already exists.', $name)
            );
        }

        $this->expressions[$name] = $expression;

        return $this;
    }

    /**
     * Returns whether one of the attached common table expressions declares
     * itself as operating recursively.
     *
     * @return bool
     */
    public function hasRecursiveExpressions(): bool
    {
        foreach ($this->expressions as $expression) {
            if ($expression->isRecursive()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        if (empty($this->expressions)) {
            throw new RuntimeException('Cannot compile WITH clause with no expressions.');
        }

        $expressions = [];
        foreach ($this->expressions as $expression) {
            $expressions[] = $expression->sql($generator);
        }

        $keywords = '';
        if (
            $this->isKeywordsEnabled() &&
            $this->hasRecursiveExpressions()
        ) {
            $keywords = 'RECURSIVE ';
        }

        return sprintf('WITH %s%s', $keywords, implode(', ', $expressions));
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        foreach ($this->expressions as $expression) {
            $visitor($expression);
            $expression->traverse($visitor);
        }

        return $this;
    }

    /**
     * Clones the inner expression objects.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->expressions as $key => $field) {
            $this->expressions[$key] = clone $this->expressions[$key];
        }
    }
}
