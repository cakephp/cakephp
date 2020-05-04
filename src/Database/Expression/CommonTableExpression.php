<?php
declare(strict_types=1);

namespace Cake\Database\Expression;

use Cake\Database\Exception as DatabaseException;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;
use InvalidArgumentException;

/**
 * An expression that represents a common table expression definition.
 */
class CommonTableExpression implements ExpressionInterface
{
    /**
     * The CTE name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * The field names to use for the CTE.
     *
     * @var \Cake\Database\ExpressionInterface[]|string[]
     */
    protected $fields = [];

    /**
     * The modifiers to use for the CTE.
     *
     * @var \Cake\Database\ExpressionInterface[]|string[]
     */
    protected $modifiers = [];

    /**
     * The CTE query definition.
     *
     * @var \Cake\Database\ExpressionInterface|null
     */
    protected $query;

    /**
     * Whether the CTE operates recursively.
     *
     * @var bool
     */
    protected $recursive = false;

    /**
     * Constructor.
     *
     * @param string $name The CTE name.
     * @param \Cake\Database\ExpressionInterface $query The CTE query definition.
     */
    public function __construct(?string $name = null, ?ExpressionInterface $query = null)
    {
        $this->name = $name;
        $this->query = $query;
    }

    /**
     * Returns the CTE name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets the CTE name.
     *
     * @param string $name The CTE name.
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the field names to use for the CTE.
     *
     * @return \Cake\Database\ExpressionInterface[]|string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Sets the field names to use for the CTE.
     *
     * @param \Cake\Database\ExpressionInterface[]|string[] $fields The field names to use for the CTE.
     * @return $this
     */
    public function setFields(array $fields)
    {
        foreach ($fields as $index => $field) {
            if (is_string($field)) {
                $fields[$index] = $field = new IdentifierExpression($field);
            }

            if (!($field instanceof ExpressionInterface)) {
                throw new InvalidArgumentException(sprintf(
                    'The `$fields` argument must contain only instances of `%s`, or strings, `%s` given at index `%d`.',
                    ExpressionInterface::class,
                    getTypeName($field),
                    $index
                ));
            }
        }

        $this->fields = $fields;

        return $this;
    }

    /**
     * Returns the modifiers to use for the CTE.
     *
     * @return \Cake\Database\ExpressionInterface[]|string[]
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    /**
     * Sets the modifiers to use for the CTE.
     *
     * @param \Cake\Database\ExpressionInterface[]|string[] $modifiers The modifiers to use for the CTE.
     * @return $this
     */
    public function setModifiers(array $modifiers)
    {
        foreach ($modifiers as $index => $modifier) {
            if (
                !($modifier instanceof ExpressionInterface) &&
                !is_string($modifier)
            ) {
                throw new InvalidArgumentException(sprintf(
                    'The `$modifiers` argument must contain only instances of `%s`, or strings, ' .
                        '`%s` given at index `%d`.',
                    ExpressionInterface::class,
                    getTypeName($modifier),
                    $index
                ));
            }
        }

        $this->modifiers = $modifiers;

        return $this;
    }

    /**
     * Returns the CTE query definition.
     *
     * @return \Cake\Database\ExpressionInterface|null
     */
    public function getQuery(): ?ExpressionInterface
    {
        return $this->query;
    }

    /**
     * Sets the CTE query definition.
     *
     * @param \Cake\Database\ExpressionInterface $query The CTE query definition.
     * @return $this
     */
    public function setQuery(ExpressionInterface $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Returns whether the CTE operates recursively.
     *
     * @return bool
     */
    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    /**
     * Sets whether the CTE operates recursively.
     *
     * @param bool $recursive Indicates whether the CTE query operates recursively.
     * @return $this
     */
    public function setRecursive(bool $recursive)
    {
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        if (empty($this->name)) {
            throw new DatabaseException(
                'Cannot generate SQL for common table expressions that have no name.'
            );
        }

        if (empty($this->query)) {
            throw new DatabaseException(
                'Cannot generate SQL for common table expressions that have no query.'
            );
        }

        $fields = '';
        if (!empty($this->fields)) {
            $fields = [];
            foreach ($this->fields as $field) {
                if ($field instanceof ExpressionInterface) {
                    $field = $field->sql($generator);
                }
                $fields[] = $field;
            }

            $fields = sprintf('(%s)', implode(', ', $fields));
        }

        $modifiers = '';
        if (!empty($this->modifiers)) {
            $modifiers = [];
            foreach ($this->modifiers as $modifier) {
                if ($modifier instanceof ExpressionInterface) {
                    $modifier = $modifier->sql($generator);
                }
                $modifiers[] = $modifier;
            }

            $modifiers = ' ' . implode(' ', $modifiers);
        }

        return sprintf('%s%s AS%s (%s)', $this->name, $fields, $modifiers, $this->query->sql($generator));
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $visitor)
    {
        foreach (array_merge($this->fields, $this->modifiers, [$this->query]) as $part) {
            if ($part instanceof ExpressionInterface) {
                $visitor($part);
                $part->traverse($visitor);
            }
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
        if ($this->query instanceof ExpressionInterface) {
            $this->query = clone $this->query;
        }

        foreach ($this->fields as $key => $field) {
            if ($this->fields[$key] instanceof ExpressionInterface) {
                $this->fields[$key] = clone $this->fields[$key];
            }
        }

        foreach ($this->modifiers as $key => $modifier) {
            if ($this->modifiers[$key] instanceof ExpressionInterface) {
                $this->modifiers[$key] = clone $this->modifiers[$key];
            }
        }
    }
}
