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
 * @copyright     Copyright (c) Cake Software Foundation, Inc.
 *                (https://github.com/cakephp/migrations/tree/master/LICENSE.txt)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use Cake\Database\TypeFactory;
use RuntimeException;

/**
 * Schema metadata for a single column
 *
 * Used by `TableSchema` when reflecting schema or creating tables.
 */
class Column
{
    /**
     * Constructor.
     *
     * @param string $name Name of the column
     * @param string $type Type of the column
     * @param bool $null Whether the column allows null values
     * @param mixed $default Default value for the column
     * @param int|null $length Length of the column
     * @param bool $identity Whether the column is an identity column
     * @param string|null $generated Postgres identity option (always|default)
     * @param int|null $precision Precision for decimal or float columns
     * @param int|null $increment Increment for identity columns
     * @param string|null $after Name of the column to add this column after
     * @param string|null $onUpdate MySQL 'ON UPDATE' function
     * @param string|null $comment Comment for the column
     * @param bool|null $unsigned Whether the column is unsigned
     * @param string|null $collate Collation for the column
     * @param int|null $srid SRID for geometry fields
     * @param string|null $baseType The basic schema type if the column type is a complex/custom type.
     */
    public function __construct(
        protected string $name,
        protected string $type,
        protected ?bool $null = null,
        protected mixed $default = null,
        protected ?int $length = null,
        protected bool $identity = false,
        protected ?string $generated = null,
        protected ?int $precision = null,
        protected ?int $increment = null,
        protected ?string $after = null,
        protected ?string $onUpdate = null,
        protected ?string $comment = null,
        protected ?bool $unsigned = null,
        protected ?string $collate = null,
        protected ?int $srid = null,
        protected ?string $baseType = null,
    ) {
    }

    /**
     * Sets the column name.
     *
     * @param string $name Name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the column name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the base type if defined. Will fallback to `type` if not set.
     *
     * Used to get the base type of a column when the column type is a complex/custom type.
     *
     * @return string|null
     */
    public function getBaseType(): ?string
    {
        if (isset($this->baseType)) {
            return $this->baseType;
        }
        $type = $this->type;
        if (TypeFactory::getMapped($type)) {
            $type = TypeFactory::build($type)->getBaseType();
        }

        return $this->baseType = $type;
    }

    /**
     * Sets the base type of the column.
     *
     * Used to set the base type of a column when the column type is a complex/custom type.
     *
     * @param string|null $baseType Base type
     * @return $this
     */
    public function setBaseType(?string $baseType)
    {
        $this->baseType = $baseType;

        return $this;
    }

    /**
     * Sets the column type.
     *
     * Type names are not validated, as drivers and dialects may implement
     * platform specific types that are not known by cakephp.
     *
     * Drivers are expected to handle unknown types gracefully.
     *
     * @param string $type Column type
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the column type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the column length.
     *
     * @param int|null $length Length
     * @return $this
     */
    public function setLength(?int $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Gets the column length.
     *
     * @return int|null
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * Sets whether the column allows nulls.
     *
     * @param bool $null Null
     * @return $this
     */
    public function setNull(bool $null)
    {
        $this->null = $null;

        return $this;
    }

    /**
     * Gets whether the column allows nulls.
     *
     * @return bool|null
     */
    public function getNull(): ?bool
    {
        return $this->null;
    }

    /**
     * Does the column allow nulls?
     *
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->getNull() === true;
    }

    /**
     * Sets the default column value.
     *
     * @param mixed $default Default
     * @return $this
     */
    public function setDefault(mixed $default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Gets the default column value.
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Sets generated option for identity columns. Ignored otherwise.
     *
     * @param string|null $generated Generated option
     * @return $this
     */
    public function setGenerated(?string $generated)
    {
        $this->generated = $generated;

        return $this;
    }

    /**
     * Gets generated option for identity columns. Null otherwise
     *
     * @return string|null
     */
    public function getGenerated(): ?string
    {
        return $this->generated;
    }

    /**
     * Sets whether the column is an identity column.
     *
     * @param bool $identity Identity
     * @return $this
     */
    public function setIdentity(bool $identity)
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * Gets whether the column is an identity column.
     *
     * @return bool
     */
    public function getIdentity(): bool
    {
        return $this->identity;
    }

    /**
     * Is the column an identity column?
     *
     * @return bool
     */
    public function isIdentity(): bool
    {
        return $this->getIdentity();
    }

    /**
     * Sets the name of the column to add this column after.
     *
     * @param string $after After
     * @return $this
     */
    public function setAfter(string $after)
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Returns the name of the column to add this column after.
     *
     * Used by MySQL and MariaDB in ALTER TABLE statements.
     *
     * @return string|null
     */
    public function getAfter(): ?string
    {
        return $this->after;
    }

    /**
     * Sets the 'ON UPDATE' mysql column function.
     *
     * Used by MySQL and MariaDB in ALTER TABLE statements.
     *
     * @param string $update On Update function
     * @return $this
     */
    public function setOnUpdate(string $update)
    {
        $this->onUpdate = $update;

        return $this;
    }

    /**
     * Returns the value of the ON UPDATE column function.
     *
     * @return string|null
     */
    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }

    /**
     * Sets the number precision for decimal or float column.
     *
     * For example `DECIMAL(5,2)`, 5 is the length and 2 is the precision,
     * and the column could store value from -999.99 to 999.99.
     *
     * @param int|null $precision Number precision
     * @return $this
     */
    public function setPrecision(?int $precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Gets the number precision for decimal or float column.
     *
     * For example `DECIMAL(5,2)`, 5 is the length and 2 is the precision,
     * and the column could store value from -999.99 to 999.99.
     *
     * @return int|null
     */
    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    /**
     * Sets the column identity increment.
     *
     * @param int $increment Number increment
     * @return $this
     */
    public function setIncrement(int $increment)
    {
        $this->increment = $increment;

        return $this;
    }

    /**
     * Gets the column identity increment.
     *
     * @return int|null
     */
    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    /**
     * Sets the column comment.
     *
     * @param string|null $comment Comment
     * @return $this
     */
    public function setComment(?string $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Gets the column comment.
     *
     * @return string
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Sets whether field should be unsigned.
     *
     * @param bool $unsigned Signed
     * @return $this
     */
    public function setUnsigned(bool $unsigned)
    {
        $this->unsigned = $unsigned;

        return $this;
    }

    /**
     * Gets whether field should be unsigned.
     *
     * @return bool|null
     */
    public function getUnsigned(): ?bool
    {
        return $this->unsigned;
    }

    /**
     * Should the column be signed?
     *
     * @return bool
     */
    public function isSigned(): bool
    {
        return !$this->getUnsigned();
    }

    /**
     * Should the column be unsigned?
     *
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->getUnsigned() === true;
    }

    /**
     * Sets the column collation.
     *
     * @param string $collation Collation
     * @return $this
     */
    public function setCollate(string $collation)
    {
        $this->collate = $collation;

        return $this;
    }

    /**
     * Gets the column collation.
     *
     * @return string|null
     */
    public function getCollate(): ?string
    {
        return $this->collate;
    }

    /**
     * Sets the column SRID for geometry fields.
     *
     * @param int $srid SRID
     * @return $this
     */
    public function setSrid(int $srid)
    {
        $this->srid = $srid;

        return $this;
    }

    /**
     * Gets the column SRID from geometry fields.
     *
     * @return int|null
     */
    public function getSrid(): ?int
    {
        return $this->srid;
    }

    /**
     * Gets all allowed options. Each option must have a corresponding `setFoo` method.
     *
     * @return array
     */
    protected function getValidOptions(): array
    {
        return [
            'name',
            'length',
            'precision',
            'default',
            'null',
            'identity',
            'after',
            'onUpdate',
            'comment',
            'unsigned',
            'type',
            'properties',
            'collate',
            'srid',
            'increment',
            'generated',
        ];
    }

    /**
     * Utility method that maps an array of column attributes to this object's methods.
     *
     * @param array<string, mixed> $attributes Attributes
     * @throws \RuntimeException
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $validOptions = $this->getValidOptions();
        if (isset($attributes['identity']) && $attributes['identity'] && !isset($attributes['null'])) {
            $attributes['null'] = false;
        }

        foreach ($attributes as $attribute => $value) {
            if (!in_array($attribute, $validOptions, true)) {
                throw new RuntimeException(sprintf('"%s" is not a valid column option.', $attribute));
            }

            $method = 'set' . ucfirst($attribute);
            $this->$method($value);
        }

        return $this;
    }

    /**
     * Convert an index into an array that is compatible with the Column constructor.
     *
     * @return array
     */
    public function toArray(): array
    {
        $type = $this->getType();
        $length = $this->getLength();
        $precision = $this->getPrecision();
        if ($precision !== null && $precision > 0) {
            if ($type === TableSchemaInterface::TYPE_TIMESTAMP) {
                $type = 'timestampfractional';
            } elseif ($type === TableSchemaInterface::TYPE_DATETIME) {
                $type = 'datetimefractional';
            }
        }

        return [
            'name' => $this->getName(),
            'baseType' => $this->getBaseType(),
            'type' => $type,
            'length' => $length,
            'null' => $this->getNull(),
            'default' => $this->getDefault(),
            'generated' => $this->getGenerated(),
            'unsigned' => $this->getUnsigned(),
            'onUpdate' => $this->getOnUpdate(),
            'collate' => $this->getCollate(),
            'precision' => $precision,
            'srid' => $this->getSrid(),
            'comment' => $this->getComment(),
            'autoIncrement' => $this->getIdentity(),
            'identity' => $this->getIdentity(),
        ];
    }
}
