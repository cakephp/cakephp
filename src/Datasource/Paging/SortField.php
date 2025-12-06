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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Paging;

/**
 * Represents a sort field configuration for pagination.
 */
class SortField
{
    /**
     * Ascending sort direction
     *
     * @var string
     */
    public const ASC = 'asc';

    /**
     * Descending sort direction
     *
     * @var string
     */
    public const DESC = 'desc';

    /**
     * Constructor.
     *
     * @param string $field The field name to sort by
     * @param string|null $defaultDirection The default sort direction
     * @param bool $locked Whether the sort direction is locked
     */
    public function __construct(
        protected string $field,
        protected ?string $defaultDirection = null,
        protected bool $locked = false,
    ) {
    }

    /**
     * Create a sort field with ascending default direction.
     *
     * @param string $field The field name to sort by
     * @param bool $locked Whether the sort direction is locked
     * @return self
     */
    public static function asc(string $field, bool $locked = false): self
    {
        return new self($field, self::ASC, $locked);
    }

    /**
     * Create a sort field with descending default direction.
     *
     * @param string $field The field name to sort by
     * @param bool $locked Whether the sort direction is locked
     * @return self
     */
    public static function desc(string $field, bool $locked = false): self
    {
        return new self($field, self::DESC, $locked);
    }

    /**
     * Get the field name.
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Get the sort direction to use.
     *
     * @param string $requestedDirection The direction requested by the user
     * @param bool $directionSpecified Whether a direction was explicitly specified
     * @return string
     */
    public function getDirection(string $requestedDirection, bool $directionSpecified): string
    {
        if ($this->locked) {
            return $this->defaultDirection ?? self::ASC;
        }

        if (!$directionSpecified && $this->defaultDirection) {
            return $this->defaultDirection;
        }

        if ($this->defaultDirection === static::DESC) {
            return $requestedDirection === static::DESC ? static::ASC : static::DESC;
        }

        return $requestedDirection;
    }

    /**
     * Check if the sort direction is locked.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }
}
