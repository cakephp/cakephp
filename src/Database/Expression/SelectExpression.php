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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\Query;
use Cake\Database\ValueBinder;

/**
 * Represents an SQL select expression similar that is a select clause that
 * holds a list of scalar values or expressions but does not include a prefix.
 */
class SelectExpression extends QueryExpression
{
    /**
     * @var string[] Storage of set modifiers such as DISTINCT that prepend to the expression.
     */
    protected $modifiers = [];

    /**
     * @inheritDoc
     */
    public function __construct($conditions = [], $types = [], $conjunction = ',')
    {
        parent::__construct($conditions, $types, $conjunction);
        $this->setConjunction($conjunction, false)->setWrappers('', '');
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $generator): string
    {
        $len = $this->count();
        if ($len === 0) {
            return '';
        }
        $typeMap = $this->getTypeMap();
        $conjunction = $this->_conjunction;
        [$conjPrefix, $conjSuffix] = $this->getConjunctionWrapped();
        [$prefix, $suffix] = $this->getWrappers();
        $modifiers = $this->getModifiers();
        $template = (!empty($modifiers) ? implode(' ', $this->getModifiers()) . ' ' : '') .
            ($len === 1 ? '%s' : $prefix . '%s' . $suffix);
        $parts = [];
        foreach ($this->_conditions as $key => $part) {
            if ($part instanceof Query) {
                $part = '(' . $part->sql($generator) . ')';
            } elseif ($part instanceof ExpressionInterface) {
                $part = $part->sql($generator);
            } else {
                $p = $generator->placeholder('se');
                $generator->bind($p, $part, $typeMap->type($key));
                $part = $p;
            }
            if (strlen($part)) {
                $parts[] = $part;
            }
        }

        return sprintf(
            $template,
            implode(($conjPrefix ? ' ' : '') . $conjunction . ($conjSuffix ? ' ' : ''), $parts)
        );
    }

    /**
     * @inheritDoc
     */
    public function add($conditions, array $types = [])
    {
        $this->getTypeMap()->setTypes(array_merge($this->getTypeMap()->getTypes(), $types));

        if (!is_array($conditions)) {
            $this->_conditions[] = $conditions;
        } else {
            foreach ($conditions as $key => $condition) {
                if (is_array($condition)) {
                    $this->_conditions[] = new static($this->_conditions, $this->getTypeMap()->type($key));
                } else {
                    if (!is_numeric($key) && ($condition == 'literal' || $condition == 'identifier')) {
                        $this->_conditions[] = new IdentifierExpression($key);
                    } else {
                        $this->_conditions[] = $condition;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Adds a modifier to the set of modifiers to be applied to the expression.
     *
     * @param string $modifier The modifier to apply to the expression.
     * @return $this
     */
    public function addModifier(string $modifier)
    {
        $this->modifiers[strtoupper($modifier)] = true;

        return $this;
    }

    /**
     * Removes the passed modifier if it exists within the set of modifiers.
     *
     * @param string $modifier The modifier to apply to the expression.
     * @return $this
     */
    public function removeModifier(string $modifier)
    {
        $modifier = strtoupper($modifier);
        if (isset($this->modifiers[$modifier])) {
            unset($this->modifiers[$modifier]);
        }

        return $this;
    }

    /**
     * Method that returns the modifiers as either an associative array where
     * the modifiers are keys or as an array where the modifiers are array
     * values.
     *
     * @param bool $asValue Boolean determining return format.
     * @return string[]
     */
    public function getModifiers(bool $asValue = true): array
    {
        return $asValue ? array_keys($this->modifiers) : $this->modifiers;
    }
}
