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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Schema;

use Cake\Database\Connection;
use Cake\Database\Exception\DatabaseException;

/**
 * Represents a single table in a database schema.
 *
 * Can either be populated using the reflection API's
 * or by incrementally building an instance using
 * methods.
 *
 * Once created TableSchema instances can be added to
 * Schema\Collection objects. They can also be converted into SQL using the
 * createSql(), dropSql() and truncateSql() methods.
 */
class TableSchema implements TableSchemaInterface, SqlGeneratorInterface
{
    /**
     * The name of the table
     *
     * @var string
     */
    protected string $_table;

    /**
     * Columns in the table.
     *
     * @var array<string, \Cake\Database\Schema\Column>
     */
    protected array $_columns = [];

    /**
     * A map with columns to types
     *
     * @var array<string, string>
     */
    protected array $_typeMap = [];

    /**
     * Indexes in the table.
     *
     * @var array<string, \Cake\Database\Schema\Index>
     */
    protected array $_indexes = [];

    /**
     * Constraints in the table.
     *
     * @var array<string, \Cake\Database\Schema\Constraint>
     */
    protected array $_constraints = [];

    /**
     * Options for the table.
     *
     * @var array<string, mixed>
     */
    protected array $_options = [];

    /**
     * Whether the table is temporary
     *
     * @var bool
     */
    protected bool $_temporary = false;

    /**
     * Column length when using a `tiny` column type
     *
     * @var int
     */
    public const LENGTH_TINY = 255;

    /**
     * Column length when using a `medium` column type
     *
     * @var int
     */
    public const LENGTH_MEDIUM = 16777215;

    /**
     * Column length when using a `long` column type
     *
     * @var int
     */
    public const LENGTH_LONG = 4294967295;

    /**
     * Valid column length that can be used with text type columns
     *
     * @var array<string, int>
     */
    public static array $columnLengths = [
        'tiny' => self::LENGTH_TINY,
        'medium' => self::LENGTH_MEDIUM,
        'long' => self::LENGTH_LONG,
    ];

    /**
     * The valid keys that can be used in a column
     * definition.
     *
     * @var array<string, mixed>
     */
    protected static array $_columnKeys = [
        'type' => null,
        'baseType' => null,
        'length' => null,
        'precision' => null,
        'null' => null,
        'default' => null,
        'comment' => null,
    ];

    /**
     * Additional type specific properties.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $_columnExtras = [
        'string' => [
            'collate' => null,
        ],
        'char' => [
            'collate' => null,
        ],
        'text' => [
            'collate' => null,
        ],
        'tinyinteger' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'smallinteger' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'integer' => [
            'unsigned' => null,
            'autoIncrement' => null,
            'generated' => null,
        ],
        'biginteger' => [
            'unsigned' => null,
            'autoIncrement' => null,
            'generated' => null,
        ],
        'decimal' => [
            'unsigned' => null,
        ],
        'float' => [
            'unsigned' => null,
        ],
        'geometry' => [
            'srid' => null,
        ],
        'point' => [
            'srid' => null,
        ],
        'linestring' => [
            'srid' => null,
        ],
        'polygon' => [
            'srid' => null,
        ],
        'datetime' => [
            'onUpdate' => null,
        ],
        'datetimefractional' => [
            'onUpdate' => null,
        ],
        'timestamp' => [
            'onUpdate' => null,
        ],
        'timestampfractional' => [
            'onUpdate' => null,
        ],
        'timestamptimezone' => [
            'onUpdate' => null,
        ],
    ];

    /**
     * The valid keys that can be used in an index
     * definition.
     *
     * @var array<string, mixed>
     */
    protected static array $_indexKeys = [
        'type' => null,
        'columns' => [],
        'length' => [],
        'references' => [],
        'include' => null,
        'update' => 'restrict',
        'delete' => 'restrict',
        'constraint' => null,
        'deferrable' => null,
        'expression' => null,
    ];

    /**
     * Names of the valid index types.
     *
     * @var array<string>
     */
    protected static array $_validIndexTypes = [
        self::INDEX_INDEX,
        self::INDEX_FULLTEXT,
    ];

    /**
     * Names of the valid constraint types.
     *
     * @var array<string>
     */
    protected static array $_validConstraintTypes = [
        self::CONSTRAINT_PRIMARY,
        self::CONSTRAINT_UNIQUE,
        self::CONSTRAINT_FOREIGN,
        self::CONSTRAINT_CHECK,
    ];

    /**
     * Names of the valid foreign key actions.
     *
     * @var array<string>
     */
    protected static array $_validForeignKeyActions = [
        self::ACTION_CASCADE,
        self::ACTION_SET_NULL,
        self::ACTION_SET_DEFAULT,
        self::ACTION_NO_ACTION,
        self::ACTION_RESTRICT,
    ];

    /**
     * Primary constraint type
     *
     * @var string
     */
    public const CONSTRAINT_PRIMARY = 'primary';

    /**
     * Unique constraint type
     *
     * @var string
     */
    public const CONSTRAINT_UNIQUE = 'unique';

    /**
     * Foreign constraint type
     *
     * @var string
     */
    public const CONSTRAINT_FOREIGN = 'foreign';

    /**
     * check constraint type
     *
     * @var string
     */
    public const CONSTRAINT_CHECK = 'check';

    /**
     * Index - index type
     *
     * @var string
     */
    public const INDEX_INDEX = Index ::INDEX;

    /**
     * Fulltext index type
     *
     * @var string
     */
    public const INDEX_FULLTEXT = Index::FULLTEXT;

    /**
     * Foreign key cascade action
     *
     * @var string
     */
    public const ACTION_CASCADE = ForeignKey::CASCADE;

    /**
     * Foreign key set null action
     *
     * @var string
     */
    public const ACTION_SET_NULL = ForeignKey::SET_NULL;

    /**
     * Foreign key no action
     *
     * @var string
     */
    public const ACTION_NO_ACTION = ForeignKey::NO_ACTION;

    /**
     * Foreign key restrict action
     *
     * @var string
     */
    public const ACTION_RESTRICT = ForeignKey::RESTRICT;

    /**
     * Foreign key restrict default
     *
     * @var string
     */
    public const ACTION_SET_DEFAULT = ForeignKey::SET_DEFAULT;

    /**
     * Constructor.
     *
     * @param string $table The table name.
     * @param array<string, array|string> $columns The list of columns for the schema.
     */
    public function __construct(string $table, array $columns = [])
    {
        $this->_table = $table;
        foreach ($columns as $field => $definition) {
            $this->addColumn($field, $definition);
        }
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->_table;
    }

    /**
     * @inheritDoc
     */
    public function addColumn(string $name, array|string $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $valid = static::$_columnKeys;
        if (isset(static::$_columnExtras[$attrs['type']])) {
            $valid += static::$_columnExtras[$attrs['type']];
        }

        $attrs = array_intersect_key($attrs, $valid);
        $attrs['name'] = $name;
        foreach (array_keys($attrs) as $key) {
            $value = $attrs[$key];
            if ($value === null) {
                unset($attrs[$key]);
                continue;
            }
            if ($key === 'autoIncrement') {
                $attrs['identity'] = $value;
                unset($attrs[$key]);
                continue;
            }
            $attrs[$key] = $value;
        }
        $column = new Column(...$attrs);

        $this->_columns[$name] = $column;
        $this->_typeMap[$name] = $column->getType();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeColumn(string $name)
    {
        unset($this->_columns[$name], $this->_typeMap[$name]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function columns(): array
    {
        return array_keys($this->_columns);
    }

    /**
     * @inheritDoc
     */
    public function getColumn(string $name): ?array
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }
        $column = $this->_columns[$name];
        $attrs = $column->toArray();

        $expected = static::$_columnKeys;
        if (isset(static::$_columnExtras[$attrs['type']])) {
            $expected += static::$_columnExtras[$attrs['type']];
        }
        // Remove any attributes that weren't in the allow list.
        // This is to provide backwards compatible keys
        $remove = array_diff(array_keys($attrs), array_keys($expected));
        foreach ($remove as $key) {
            unset($attrs[$key]);
        }

        if (isset($attrs['baseType']) && $attrs['baseType'] === $attrs['type']) {
            unset($attrs['baseType']);
        }

        return $attrs;
    }

    /**
     * Get a column object for a given column name.
     *
     * Will raise an exception if the column does not exist.
     *
     * @param string $name The name of the column to get.
     * @return \Cake\Database\Schema\Column
     */
    public function column(string $name): Column
    {
        $column = $this->_columns[$name] ?? null;
        if ($column === null) {
            $message = sprintf(
                'Table `%s` does not contain a column named `%s`.',
                $this->_table,
                $name,
            );
            throw new DatabaseException($message);
        }

        return $column;
    }

    /**
     * @inheritDoc
     */
    public function getColumnType(string $name): ?string
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }

        return $this->_columns[$name]->getType();
    }

    /**
     * @inheritDoc
     */
    public function setColumnType(string $name, string $type)
    {
        if (!isset($this->_columns[$name])) {
            $message = sprintf(
                'Column `%s` of table `%s`: The column type `%s` can only be set if the column already exists;',
                $name,
                $this->_table,
                $type,
            );
            $message .= ' can be checked using `hasColumn()`.';

            throw new DatabaseException($message);
        }

        $this->_columns[$name]
            ->setType($type)
            ->setBaseType(null);
        $this->_typeMap[$name] = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasColumn(string $name): bool
    {
        return isset($this->_columns[$name]);
    }

    /**
     * @inheritDoc
     */
    public function baseColumnType(string $column): ?string
    {
        if (!isset($this->_columns[$column])) {
            return null;
        }

        return $this->_columns[$column]->getBaseType();
    }

    /**
     * @inheritDoc
     */
    public function typeMap(): array
    {
        return $this->_typeMap;
    }

    /**
     * @inheritDoc
     */
    public function isNullable(string $name): bool
    {
        if (!isset($this->_columns[$name])) {
            return true;
        }

        return $this->_columns[$name]->getNull() === true;
    }

    /**
     * @inheritDoc
     */
    public function defaultValues(): array
    {
        $defaults = [];
        foreach ($this->_columns as $column) {
            $default = $column->getDefault();
            if ($default === null && $column->getNull() !== true && $column->getName()) {
                continue;
            }
            $defaults[$column->getName()] = $default;
        }

        return $defaults;
    }

    /**
     * @inheritDoc
     */
    public function addIndex(string $name, array|string $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$_indexKeys);
        $attrs += static::$_indexKeys;
        unset(
            $attrs['references'],
            $attrs['update'],
            $attrs['delete'],
            $attrs['constraint'],
            $attrs['deferrable'],
            $attrs['expression'],
        );

        if (!in_array($attrs['type'], static::$_validIndexTypes, true)) {
            throw new DatabaseException(sprintf(
                'Invalid index type `%s` in index `%s` in table `%s`.',
                $attrs['type'],
                $name,
                $this->_table,
            ));
        }
        $attrs['columns'] = (array)$attrs['columns'];
        foreach ($attrs['columns'] as $field) {
            if (empty($this->_columns[$field])) {
                $msg = sprintf(
                    'Columns used in index `%s` in table `%s` must be added to the Table schema first. ' .
                    'The column `%s` was not found.',
                    $name,
                    $this->_table,
                    $field,
                );
                throw new DatabaseException($msg);
            }
        }
        $attrs['name'] = $name;

        $this->_indexes[$name] = new Index(...$attrs);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function indexes(): array
    {
        return array_keys($this->_indexes);
    }

    /**
     * @inheritDoc
     */
    public function getIndex(string $name): ?array
    {
        if (!isset($this->_indexes[$name])) {
            return null;
        }
        $index = $this->_indexes[$name];
        $attrs = $index->toArray();

        $optional = ['order', 'include', 'where'];
        foreach ($optional as $key) {
            if ($attrs[$key] === null) {
                unset($attrs[$key]);
            }
        }
        unset($attrs['name']);

        return $attrs;
    }

    /**
     * Get a index object for a given index name.
     *
     * Will raise an exception if no index can be found.
     *
     * @param string $name The name of the index to get.
     * @return \Cake\Database\Schema\Index
     */
    public function index(string $name): Index
    {
        $index = $this->_indexes[$name] ?? null;
        if ($index === null) {
            $message = sprintf(
                'Table `%s` does not contain a index named `%s`.',
                $this->_table,
                $name,
            );
            throw new DatabaseException($message);
        }

        return $index;
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKey(): array
    {
        foreach ($this->_constraints as $data) {
            if ($data->getType() === static::CONSTRAINT_PRIMARY) {
                return (array)$data->getColumns();
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function addConstraint(string $name, array|string $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$_indexKeys);
        $attrs += static::$_indexKeys;
        if ($attrs['constraint'] === null) {
            unset($attrs['constraint']);
        }

        if (!in_array($attrs['type'], static::$_validConstraintTypes, true)) {
            throw new DatabaseException(sprintf(
                'Invalid constraint type `%s` in table `%s`.',
                $attrs['type'],
                $this->_table,
            ));
        }
        if ($attrs['type'] !== TableSchema::CONSTRAINT_CHECK) {
            if (empty($attrs['columns'])) {
                throw new DatabaseException(sprintf(
                    'Constraints in table `%s` must have at least one column.',
                    $this->_table,
                ));
            }
            $attrs['columns'] = (array)$attrs['columns'];
            foreach ($attrs['columns'] as $field) {
                if (empty($this->_columns[$field])) {
                    $msg = sprintf(
                        'Columns used in constraints must be added to the Table schema first. ' .
                        'The column `%s` was not found in table `%s`.',
                        $field,
                        $this->_table,
                    );
                    throw new DatabaseException($msg);
                }
            }
        }

        $attrs['name'] = $attrs['constraint'] ?? $name;
        unset($attrs['constraint'], $attrs['include']);

        $type = $attrs['type'] ?? null;
        if ($type === static::CONSTRAINT_FOREIGN) {
            $attrs = $this->_checkForeignKey($attrs);
        } elseif ($type === static::CONSTRAINT_PRIMARY) {
            $attrs = [
                'type' => $type,
                'name' => $attrs['name'],
                'columns' => $attrs['columns'],
            ];
        } elseif ($type === static::CONSTRAINT_CHECK) {
            $attrs = [
                'name' => $attrs['name'],
                'expression' => $attrs['expression'],
            ];
        } elseif ($type === static::CONSTRAINT_UNIQUE) {
            $attrs = [
                'name' => $attrs['name'],
                'columns' => $attrs['columns'],
                'length' => $attrs['length'],
            ];
        }
        if ($type === static::CONSTRAINT_FOREIGN) {
            $constraint = $this->_constraints[$name] ?? null;
            if ($constraint instanceof ForeignKey) {
                // Update an existing foreign key constraint.
                // This is backwards compatible with the incremental
                // build API that I would like to deprecate.
                $constraint->setColumns(array_unique(array_merge(
                    (array)$constraint->getColumns(),
                    $attrs['columns'],
                )));

                if ($constraint->getReferencedTable()) {
                    $constraint->setColumns(array_unique(array_merge(
                        (array)$constraint->getReferencedColumns(),
                        [$attrs['references'][1]],
                    )));
                }

                return $this;
            }
        }

        $this->_constraints[$name] = match ($type) {
            static::CONSTRAINT_UNIQUE => new UniqueKey(...$attrs),
            static::CONSTRAINT_FOREIGN => new ForeignKey(...$attrs),
            static::CONSTRAINT_PRIMARY => new Constraint(...$attrs),
            static::CONSTRAINT_CHECK => new CheckConstraint(...$attrs),
            default => new Constraint(...$attrs),
        };

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function dropConstraint(string $name)
    {
        if (isset($this->_constraints[$name])) {
            unset($this->_constraints[$name]);
        }

        return $this;
    }

    /**
     * Check whether a table has an autoIncrement column defined.
     *
     * @return bool
     */
    public function hasAutoincrement(): bool
    {
        foreach ($this->_columns as $column) {
            if ($column->getIdentity()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to check/validate foreign keys.
     *
     * @param array<string, mixed> $attrs Attributes to set.
     * @return array<string, mixed>
     * @throws \Cake\Database\Exception\DatabaseException When foreign key definition is not valid.
     */
    protected function _checkForeignKey(array $attrs): array
    {
        if (count($attrs['references']) < 2) {
            throw new DatabaseException('References must contain a table and column.');
        }
        if (!in_array($attrs['update'], static::$_validForeignKeyActions)) {
            throw new DatabaseException(sprintf(
                'Update action is invalid. Must be one of %s',
                implode(',', static::$_validForeignKeyActions),
            ));
        }
        if (!in_array($attrs['delete'], static::$_validForeignKeyActions)) {
            throw new DatabaseException(sprintf(
                'Delete action is invalid. Must be one of %s',
                implode(',', static::$_validForeignKeyActions),
            ));
        }

        // Map the backwards compatible attributes in. Need to check for existing instance.
        $attrs['referencedTable'] = $attrs['references'][0];
        $attrs['referencedColumns'] = (array)$attrs['references'][1];
        unset($attrs['type'], $attrs['references'], $attrs['length'], $attrs['expression']);

        return $attrs;
    }

    /**
     * @inheritDoc
     */
    public function constraints(): array
    {
        return array_keys($this->_constraints);
    }

    /**
     * @inheritDoc
     */
    public function getConstraint(string $name): ?array
    {
        $constraint = $this->_constraints[$name] ?? null;
        if ($constraint === null) {
            return null;
        }

        $data = $constraint->toArray();
        if ($constraint instanceof ForeignKey) {
            $data['references'] = [
                $constraint->getReferencedTable(),
                $constraint->getReferencedColumns(),
            ];
            // If there is only one referenced column, we return it as a string.
            // TODO this should be deprecated, but I don't know how to warn about it.
            if (count($data['references'][1]) === 1) {
                $data['references'][1] = $data['references'][1][0];
            }
            unset($data['referencedTable'], $data['referencedColumns']);
        }
        if ($constraint->getType() === static::CONSTRAINT_PRIMARY && $name === 'primary') {
            $alias = $constraint->getName();
            if ($alias !== 'primary') {
                $data['constraint'] = $alias;
            }
        }
        unset($data['name']);

        return $data;
    }

    /**
     * Get a constraint object for a given constraint name.
     *
     * Constraints have a few subtypes such as foreign keys and primary keys.
     * You can either use `instanceof` or getType() to check for subclass types.
     *
     * @param string $name The name of the constraint to get.
     * @return \Cake\Database\Schema\Constraint A constraint object.
     */
    public function constraint(string $name): Constraint
    {
        if (!isset($this->_constraints[$name])) {
            $message = sprintf(
                'Table `%s` does not contain a constraint named `%s`.',
                $this->_table,
                $name,
            );
            throw new DatabaseException($message);
        }

        return $this->_constraints[$name];
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        $this->_options = $options + $this->_options;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * @inheritDoc
     */
    public function setTemporary(bool $temporary)
    {
        $this->_temporary = $temporary;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isTemporary(): bool
    {
        return $this->_temporary;
    }

    /**
     * @inheritDoc
     */
    public function createSql(Connection $connection): array
    {
        $dialect = $connection->getWriteDriver()->schemaDialect();
        $columns = [];
        $constraints = [];
        $indexes = [];
        foreach (array_keys($this->_columns) as $name) {
            $columns[] = $dialect->columnSql($this, $name);
        }
        foreach (array_keys($this->_constraints) as $name) {
            $constraints[] = $dialect->constraintSql($this, $name);
        }
        foreach (array_keys($this->_indexes) as $name) {
            $indexes[] = $dialect->indexSql($this, $name);
        }

        return $dialect->createTableSql($this, $columns, $constraints, $indexes);
    }

    /**
     * @inheritDoc
     */
    public function dropSql(Connection $connection): array
    {
        $dialect = $connection->getWriteDriver()->schemaDialect();

        return $dialect->dropTableSql($this);
    }

    /**
     * @inheritDoc
     */
    public function truncateSql(Connection $connection): array
    {
        $dialect = $connection->getWriteDriver()->schemaDialect();

        return $dialect->truncateTableSql($this);
    }

    /**
     * @inheritDoc
     */
    public function addConstraintSql(Connection $connection): array
    {
        $dialect = $connection->getWriteDriver()->schemaDialect();

        return $dialect->addConstraintSql($this);
    }

    /**
     * @inheritDoc
     */
    public function dropConstraintSql(Connection $connection): array
    {
        $dialect = $connection->getWriteDriver()->schemaDialect();

        return $dialect->dropConstraintSql($this);
    }

    /**
     * Custom unserialization that handles compatibility
     * with older CakePHP versions.
     *
     * Previously the `_columns`, `_indexes`, and `_constraints`
     * attributes contained array data. As of 5.3, those attributes
     * contain arrays of objects.
     *
     * @param array<string, mixed> $data The serialized data.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->_table = $data["\0*\0_table"] ?? '';

        $columns = $data["\0*\0_columns"] ?? [];
        foreach ($columns as $name => $column) {
            $name = (string)$name;
            if (is_array($column)) {
                $this->addColumn($name, $column);
            } else {
                $this->_columns[$name] = $column;
            }
        }
        $indexes = $data["\0*\0_indexes"] ?? [];
        foreach ($indexes as $name => $index) {
            $name = (string)$name;
            if (is_array($index)) {
                $this->addIndex($name, $index);
            } else {
                $this->_indexes[$name] = $index;
            }
        }
        $constraints = $data["\0*\0_constraints"] ?? [];
        foreach ($constraints as $name => $constraint) {
            $name = (string)$name;
            if (is_array($constraint)) {
                $this->addConstraint($name, $constraint);
            } else {
                $this->_constraints[$name] = $constraint;
            }
        }
        $this->_options = $data["\0*\0_options"] ?? [];
        $this->_typeMap = $data["\0*\0_typeMap"] ?? [];
        $this->_temporary = $data["\0*\0_temporary"] ?? false;
    }

    /**
     * Returns an array of the table schema.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'table' => $this->_table,
            'columns' => $this->_columns,
            'indexes' => $this->_indexes,
            'constraints' => $this->_constraints,
            'options' => $this->_options,
            'typeMap' => $this->_typeMap,
            'temporary' => $this->_temporary,
        ];
    }
}
