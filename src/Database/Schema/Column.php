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

use RuntimeException;

/**
 * Schema metadata for a single column
 *
 * Used by `TableSchema` when reflecting schema or creating tables.
 */
class Column
{
    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var string
     */
    protected string $type = TableSchemaInterface::TYPE_STRING;

    /**
     * @var int|null
     */
    protected ?int $length = null;

    /**
     * @var bool
     */
    protected bool $null = true;

    /**
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * @var bool
     */
    protected bool $identity = false;

    /**
     * Postgres-only column option for identity (always|default)
     *
     * @var ?string
     */
    protected ?string $generated = PostgresSchemaDialect::GENERATED_BY_DEFAULT;

    /**
     * @var int|null
     */
    protected ?int $precision = null;

    /**
     * @var int|null
     */
    protected ?int $increment = null;

    /**
     * @var string|null
     */
    protected ?string $after = null;

    /**
     * @var string|null
     */
    protected ?string $onUpdate = null;

    /**
     * @var string|null
     */
    protected ?string $comment = null;

    /**
     * @var bool
     */
    protected bool $unsigned = true;

    /**
     * @var array
     */
    protected array $properties = [];

    /**
     * @var string|null
     */
    protected ?string $collate = null;

    /**
     * @var int|null
     */
    protected ?int $srid = null;

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
     * @return bool
     */
    public function getNull(): bool
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
        return $this->getNull();
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
     * @return bool
     */
    public function getUnsigned(): bool
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
        return $this->getUnsigned();
    }

    /**
     * Sets field properties.
     *
     * @param array $properties Properties
     * @return $this
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Gets field properties
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
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
     * Convert the column into the array shape
     * used by cakephp/database.
     *
     * @return array
     */
    public function toArray(): array
    {
        $type = $this->getType();
        $length = $this->getLength();
        $precision = $this->getPrecision();
        if ($precision !== null) {
            if ($type === TableSchemaInterface::TYPE_TIMESTAMP) {
                $type = 'timestampfractional';
            } elseif ($type === TableSchemaInterface::TYPE_DATETIME) {
                $type = 'datetimefractional';
            }
        }

        return [
            'name' => $this->getName(),
            'type' => $type,
            'length' => $length,
            'null' => $this->getNull(),
            'default' => $this->getDefault(),
            'unsigned' => !$this->getUnsigned(),
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
