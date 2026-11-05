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
namespace Cake\Database;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\FieldInterface;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\OrderByExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query\SelectQuery;
use Closure;
use stdClass;

/**
 * Keeps the table and column aliases of a query within the maximum identifier
 * length supported by the database.
 *
 * Some databases silently truncate identifiers that are too long (Postgres
 * truncates them to 63 bytes). The ORM generates column aliases by combining the
 * table alias and the column name (`Alias__column`), which can exceed that limit
 * and make the truncated column disappear from the results, or make two different
 * aliases collide once truncated.
 *
 * Any identifier longer than the limit is replaced in the generated SQL with a
 * shortened version made of the start of the identifier followed by a hash
 * of the full identifier. The shortened version only depends on the original
 * identifier, so every reference to it (including references from sub-queries)
 * is shortened the same way. Queries keep the original identifiers, the
 * shortening only happens on the copy of the query that gets compiled, and
 * `aliasMap()` provides what is needed to restore the original column aliases
 * in the fetched rows.
 *
 * @internal
 */
class IdentifierShortener
{
    /**
     * Length of the hash suffix added to shortened identifiers, including the separator.
     *
     * @var int
     */
    protected const SUFFIX_LENGTH = 9;

    /**
     * Pattern matching the parts of plain SQL that are not shortened with standard SQL strings:
     * line and block comments, `'...'` string literals, and Postgres `E'...'` and
     * dollar quoted string literals.
     *
     * @var string
     */
    protected const SKIP_STANDARD = '--[^\n]*|/\*.*?\*/|' .
        '\b[Ee]\'(?:[^\'\\\\]|\\\\.|\'\')*\'|\$(\w*)\$.*?\$\1\$|\'(?:[^\']|\'\')*\'';

    /**
     * Pattern matching the parts of plain SQL that are not shortened with MySQL strings:
     * `-- `, `#` and block comments, and `'...'` and `"..."` string literals,
     * which use backslash escapes.
     *
     * @var string
     */
    protected const SKIP_MYSQL = '--\s[^\n]*|#[^\n]*|/\*.*?\*/|' .
        '\'(?:[^\'\\\\]|\\\\.|\'\')*\'|"(?:[^"\\\\]|\\\\.|"")*"';

    /**
     * Pattern matching the words to shorten, skipping comments and string literals.
     *
     * Outside of comments and string literals, a word longer than the identifier
     * limit can only be an identifier.
     *
     * @var string
     */
    protected string $wordPattern;

    /**
     * Constructor
     *
     * @param int $maxLength The maximum identifier length in bytes.
     * @param bool $mysqlStrings Whether string literals follow MySQL rules: backslash escapes
     *   and double quoted strings.
     */
    public function __construct(protected int $maxLength, bool $mysqlStrings = false)
    {
        $skip = $mysqlStrings ? static::SKIP_MYSQL : static::SKIP_STANDARD;
        $this->wordPattern = '~(?:' . $skip . ')(*SKIP)(*FAIL)|\w+~su';

        if ($maxLength <= static::SUFFIX_LENGTH) {
            throw new DatabaseException(sprintf(
                'The maximum identifier length must be greater than %d, `%d` given.',
                static::SUFFIX_LENGTH,
                $maxLength,
            ));
        }
    }

    /**
     * Returns the maximum identifier length.
     *
     * @return int
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * Shortens a single identifier (without any dots or quotes) when it is longer
     * than the maximum length.
     *
     * The returned identifier is the start of the original one followed by a hash of
     * the full identifier, e.g. `Creator_Contacts_Organisations__billing_7c0c4f2a`.
     *
     * @param string $name The identifier to shorten.
     * @return string
     */
    public function shorten(string $name): string
    {
        if (strlen($name) <= $this->maxLength) {
            return $name;
        }

        return mb_strcut($name, 0, $this->maxLength - static::SUFFIX_LENGTH) . '_' . $this->hash($name);
    }

    /**
     * Shortens all the identifiers longer than the maximum length found in a string
     * such as `Alias.field`, `Alias__field`, `"Alias"."field"`, `COUNT(Alias.field)`
     * or `Alias.field > 1`.
     *
     * Comments and string literals are kept as they are.
     *
     * @param string $identifier The string to process.
     * @return string
     */
    public function shortenIdentifier(string $identifier): string
    {
        if (strlen($identifier) <= $this->maxLength) {
            return $identifier;
        }

        return (string)preg_replace_callback(
            $this->wordPattern,
            fn(array $matches): string => $this->shorten($matches[0]),
            $identifier,
        );
    }

    /**
     * Returns whether the query defines or references identifiers that are too long.
     *
     * Only the parts of the query that define table and column aliases are checked
     * (common table expressions, select, from, join and window) along with group and distinct,
     * which can only hold identifiers. Long identifiers referenced elsewhere are
     * always defined in one of those parts, either in this query or in an outer one
     * which will shorten them when it is compiled.
     *
     * @param \Cake\Database\Query $query The query to check.
     * @return bool
     */
    public function needsShortening(Query $query): bool
    {
        foreach ($query->clause('with') as $cte) {
            if ($cte instanceof CommonTableExpression && $this->hasLongName($cte)) {
                return true;
            }
        }

        if (!$query instanceof SelectQuery) {
            // Update and delete queries do not support table aliases or joins.
            return false;
        }

        foreach (['select', 'distinct', 'group', 'from'] as $part) {
            $contents = $query->clause($part);
            if (is_array($contents) && $this->hasLongIdentifier($contents)) {
                return true;
            }
        }

        foreach ($query->clause('window') as $window) {
            if (strlen($window['name']->getIdentifier()) > $this->maxLength) {
                return true;
            }
        }

        foreach ($query->clause('join') as $alias => $join) {
            if (is_string($alias) && strlen($alias) > $this->maxLength) {
                return true;
            }
            if (isset($join['alias']) && strlen($join['alias']) > $this->maxLength) {
                return true;
            }
            $table = $join['table'] ?? null;
            if (is_string($table) && $this->shortenIdentifier($table) !== $table) {
                return true;
            }
            if ($table instanceof Query && $this->needsShortening($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shortens all the identifiers longer than the maximum length in the passed query.
     *
     * The query is modified in place, callers are expected to pass a copy of the
     * query that is only used to generate SQL.
     *
     * @template T of \Cake\Database\Query
     * @param T $query The query to shorten identifiers in.
     * @return T
     */
    public function shortenQuery(Query $query): Query
    {
        if (!$query instanceof SelectQuery) {
            $query->traverseExpressions($this->shortenExpression(...));

            return $query;
        }

        $binder = $query->getValueBinder();
        $query->setValueBinder(null);

        $select = $query->clause('select');
        if ($select) {
            $query->select($this->shortenPart($select), true);
        }

        $distinct = $query->clause('distinct');
        if (is_array($distinct) && $distinct) {
            $query->distinct($this->shortenPart($distinct), true);
        }

        $group = $query->clause('group');
        if ($group) {
            $query->groupBy($this->shortenPart($group), true);
        }

        $from = $query->clause('from');
        if ($from) {
            $query->from($this->shortenPart($from), true);
        }

        $joins = $query->clause('join');
        if ($joins) {
            $result = [];
            foreach ($joins as $alias => $join) {
                if (isset($join['alias'])) {
                    $join['alias'] = $this->shortenIdentifier($join['alias']);
                }
                if (is_string($join['table'] ?? null)) {
                    $join['table'] = $this->shortenIdentifier($join['table']);
                }
                $result[is_string($alias) ? $this->shortenIdentifier($alias) : $alias] = $join;
            }
            $query->join($result, [], true);
        }

        $query->traverseExpressions($this->shortenExpression(...));

        return $query->setValueBinder($binder);
    }

    /**
     * Returns a map of the shortened column aliases to the original ones for the
     * columns selected by the passed query.
     *
     * An empty array is returned when no column alias needs shortening.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query The query to build the map for.
     * @return array<string, string>
     * @throws \Cake\Database\Exception\DatabaseException When two aliases are shortened to the same identifier.
     */
    public function aliasMap(SelectQuery $query): array
    {
        $map = [];
        $select = $query->clause('select');
        if (!is_array($select)) {
            return $map;
        }
        foreach ($select as $alias => $field) {
            if (!is_string($alias)) {
                continue;
            }
            $short = $this->shortenIdentifier($alias);
            if ($short === $alias) {
                continue;
            }
            if (isset($map[$short]) && $map[$short] !== $alias) {
                throw new DatabaseException(sprintf(
                    'The column aliases `%s` and `%s` are both shortened to `%s`. ' .
                    'Use a different alias for one of them.',
                    $map[$short],
                    $alias,
                    $short,
                ));
            }
            $map[$short] = $alias;
        }

        return $map;
    }

    /**
     * Returns a result decorator that renames the shortened column aliases of a row back
     * to the original ones, or null when the query has no shortened column aliases.
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query The query to build the decorator for.
     * @return \Closure|null
     */
    public function resultDecorator(SelectQuery $query): ?Closure
    {
        $map = $this->aliasMap($query);
        if (!$map) {
            return null;
        }

        return static function (mixed $row) use ($map): mixed {
            if ($row instanceof stdClass) {
                return (object)static::renameKeys(get_object_vars($row), $map);
            }
            if (!is_array($row)) {
                return $row;
            }

            return static::renameKeys($row, $map);
        };
    }

    /**
     * Renames the keys of a row using a map of shortened to original column aliases.
     *
     * @param array<string|int, mixed> $row The row to rename the keys of.
     * @param array<string, string> $map Shortened column aliases mapped to the original ones.
     * @return array<string|int, mixed>
     */
    protected static function renameKeys(array $row, array $map): array
    {
        $renamed = [];
        foreach ($row as $key => $value) {
            $renamed[$map[$key] ?? $key] = $value;
        }

        return $renamed;
    }

    /**
     * Shortens the identifiers found in an expression of the query.
     *
     * @param \Cake\Database\ExpressionInterface $expression The expression to process.
     * @return void
     */
    public function shortenExpression(ExpressionInterface $expression): void
    {
        if ($expression instanceof IdentifierExpression) {
            $expression->setIdentifier($this->shortenIdentifier($expression->getIdentifier()));

            return;
        }

        if ($expression instanceof FieldInterface) {
            $field = $expression->getField();
            if (is_string($field)) {
                $expression->setField($this->shortenIdentifier($field));
            } elseif (is_array($field)) {
                foreach ($field as $k => $f) {
                    if (is_string($f)) {
                        $field[$k] = $this->shortenIdentifier($f);
                    }
                }
                $expression->setField($field);
            }
        }

        if ($expression instanceof OrderByExpression) {
            $expression->iterateParts(function ($part, &$field) {
                if (is_string($field)) {
                    $field = $this->shortenIdentifier($field);
                }
                if (is_string($part) && !is_string($field)) {
                    return $this->shortenIdentifier($part);
                }

                return $part;
            });

            return;
        }

        if ($expression instanceof QueryExpression) {
            // Plain SQL conditions like `Alias.field = 1` can reference long aliases.
            $expression->iterateParts(function ($part) {
                return is_string($part) ? $this->shortenIdentifier($part) : $part;
            });
        }
    }

    /**
     * Shortens the identifiers in the keys and string values of a query part.
     *
     * @param array $part The query part.
     * @return array
     */
    protected function shortenPart(array $part): array
    {
        $result = [];
        foreach ($part as $alias => $value) {
            if (is_string($value)) {
                $value = $this->shortenIdentifier($value);
            }
            if (is_string($alias)) {
                $alias = $this->shortenIdentifier($alias);
            }
            $result[$alias] = $value;
        }

        return $result;
    }

    /**
     * Returns whether the keys or string values of a query part contain a long identifier.
     *
     * @param array $part The query part.
     * @return bool
     */
    protected function hasLongIdentifier(array $part): bool
    {
        foreach ($part as $alias => $value) {
            if (is_string($alias) && $this->shortenIdentifier($alias) !== $alias) {
                return true;
            }
            if (is_string($value) && $this->shortenIdentifier($value) !== $value) {
                return true;
            }
            if ($value instanceof Query && $this->needsShortening($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the name or the column names of a common table expression are too long.
     *
     * @param \Cake\Database\Expression\CommonTableExpression $cte The common table expression.
     * @return bool
     */
    protected function hasLongName(CommonTableExpression $cte): bool
    {
        $found = false;
        $inQuery = false;
        // The name and the columns are traversed before the query of the expression.
        $cte->traverse(function (mixed $expression) use (&$found, &$inQuery): void {
            if ($expression instanceof Query) {
                $inQuery = true;
            }
            if (
                !$inQuery &&
                $expression instanceof IdentifierExpression &&
                strlen($expression->getIdentifier()) > $this->maxLength
            ) {
                $found = true;
            }
        });

        return $found;
    }

    /**
     * Returns the hash used to tell apart shortened identifiers.
     *
     * @param string $name The full identifier.
     * @return string An 8 characters long hash.
     */
    protected function hash(string $name): string
    {
        return hash('xxh32', $name);
    }
}
