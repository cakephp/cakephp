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
     * Column length when using a `tiny` column type
     *
     * @var int
     */
    public const int LENGTH_TINY = 255;

    /**
     * Column length when using a `medium` column type
     *
     * @var int
     */
    public const int LENGTH_MEDIUM = 16777215;

    /**
     * Column length when using a `long` column type
     *
     * @var int
     */
    public const int LENGTH_LONG = 4294967295;

    /**
     * Primary constraint type
     *
     * @var string
     */
    public const string CONSTRAINT_PRIMARY = 'primary';

    /**
     * Unique constraint type
     *
     * @var string
     */
    public const string CONSTRAINT_UNIQUE = 'unique';

    /**
     * Foreign constraint type
     *
     * @var string
     */
    public const string CONSTRAINT_FOREIGN = 'foreign';

    /**
     * check constraint type
     *
     * @var string
     */
    public const string CONSTRAINT_CHECK = 'check';

    /**
     * Index - index type
     *
     * @var string
     */
    public const string INDEX_INDEX = Index::INDEX;

    /**
     * Fulltext index type
     *
     * @var string
     */
    public const string INDEX_FULLTEXT = Index::FULLTEXT;

    /**
     * Foreign key cascade action
     *
     * @var string
     */
    public const string ACTION_CASCADE = ForeignKey::CASCADE;

    /**
     * Foreign key set null action
     *
     * @var string
     */
    public const string ACTION_SET_NULL = ForeignKey::SET_NULL;

    /**
     * Foreign key no action
     *
     * @var string
     */
    public const string ACTION_NO_ACTION = ForeignKey::NO_ACTION;

    /**
     * Foreign key restrict action
     *
     * @var string
     */
    public const string ACTION_RESTRICT = ForeignKey::RESTRICT;

    /**
     * Foreign key restrict default
     *
     * @var string
     */
    public const string ACTION_SET_DEFAULT = ForeignKey::SET_DEFAULT;

    /**
     * Columns in the table.
     *
     * @var array<string, \Cake\Database\Schema\Column>
     */
    protected array $columns = [];

    /**
     * A map with columns to types
     *
     * @var array<string, string>
     */
    protected array $typeMap = [];

    /**
     * Indexes in the table.
     *
     * @var array<string, \Cake\Database\Schema\Index>
     */
    protected array $indexes = [];

    /**
     * Constraints in the table.
     *
     * @var array<string, \Cake\Database\Schema\Constraint>
     */
    protected array $constraints = [];

    /**
     * Options for the table.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Whether the table is temporary
     *
     * @var bool
     */
    protected bool $temporary = false;

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
    protected static array $columnKeys = [
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
    protected static array $columnExtras = [
        'string' => [
            'collate' => null,
        ],
        'char' => [
            'collate' => null,
        ],
        'text' => [
            'collate' => null,
        ],
        'uuid' => [
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
            'geometryType' => null,
            'srid' => null,
        ],
        'geography' => [
            'geometryType' => null,
            'srid' => null,
        ],
        'point' => [
            'geometryType' => null,
            'srid' => null,
        ],
        'linestring' => [
            'geometryType' => null,
            'srid' => null,
        ],
        'polygon' => [
            'geometryType' => null,
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
        'binary' => [
            'fixed' => null,
        ],
    ];

    /**
     * The valid keys that can be used in an index
     * definition.
     *
     * @var array<string, mixed>
     */
    protected static array $indexKeys = [
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
    protected static array $validIndexTypes = [
        self::INDEX_INDEX,
        self::INDEX_FULLTEXT,
    ];

    /**
     * Names of the valid constraint types.
     *
     * @var array<string>
     */
    protected static array $validConstraintTypes = [
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
    protected static array $validForeignKeyActions = [
        self::ACTION_CASCADE,
        self::ACTION_SET_NULL,
        self::ACTION_SET_DEFAULT,
        self::ACTION_NO_ACTION,
        self::ACTION_RESTRICT,
    ];

    /**
     * Constructor.
     *
     * @param string $table The table name.
     * @param array<string, array|string> $columns The list of columns for the schema.
     */
    public function __construct(
        protected string $table,
        array $columns = [],
    ) {
        foreach ($columns as $field => $definition) {
            $this->addColumn($field, $definition);
        }
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->table;
    }

    /**
     * @inheritDoc
     */
    public function addColumn(string $name, array|string $attrs): static
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $valid = static::$columnKeys;
        if (isset(static::$columnExtras[$attrs['type']])) {
            $valid += static::$columnExtras[$attrs['type']];
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

        // Cast numeric values that may come as floats from database drivers.
        // PHP 8.4 is stricter about implicit float-to-int conversions.
        // Known to affect SQLite on Windows x86.
        foreach (['length', 'precision', 'srid'] as $key) {
            if (isset($attrs[$key])) {
                $attrs[$key] = (int)$attrs[$key];
            }
        }

        $column = new Column(...$attrs);

        $this->columns[$name] = $column;
        $this->typeMap[$name] = $column->getType();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeColumn(string $name): static
    {
        unset($this->columns[$name], $this->typeMap[$name]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function columns(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @inheritDoc
     */
    public function getColumn(string $name): ?array
    {
        if (!isset($this->columns[$name])) {
            return null;
        }
        $column = $this->columns[$name];
        $attrs = $column->toArray();

        $expected = static::$columnKeys;
        if (isset(static::$columnExtras[$attrs['type']])) {
            $expected += static::$columnExtras[$attrs['type']];
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
        $column = $this->columns[$name] ?? null;
        if ($column === null) {
            $message = sprintf(
                'Table `%s` does not contain a column named `%s`.',
                $this->table,
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
        if (!isset($this->columns[$name])) {
            return null;
        }

        return $this->columns[$name]->getType();
    }

    /**
     * @inheritDoc
     */
    public function setColumnType(string $name, string $type): static
    {
        if (!isset($this->columns[$name])) {
            $message = sprintf(
                'Column `%s` of table `%s`: The column type `%s` can only be set if the column already exists;',
                $name,
                $this->table,
                $type,
            );
            $message .= ' can be checked using `hasColumn()`.';

            throw new DatabaseException($message);
        }

        $this->columns[$name]
            ->setType($type)
            ->setBaseType(null);
        $this->typeMap[$name] = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * @inheritDoc
     */
    public function baseColumnType(string $column): ?string
    {
        if (!isset($this->columns[$column])) {
            return null;
        }

        return $this->columns[$column]->getBaseType();
    }

    /**
     * @inheritDoc
     */
    public function typeMap(): array
    {
        return $this->typeMap;
    }

    /**
     * @inheritDoc
     */
    public function isNullable(string $name): bool
    {
        if (!isset($this->columns[$name])) {
            return true;
        }

        return $this->columns[$name]->getNull() === true;
    }

    /**
     * @inheritDoc
     */
    public function defaultValues(): array
    {
        $defaults = [];
        foreach ($this->columns as $column) {
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
    public function addIndex(string $name, array|string $attrs): static
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$indexKeys);
        $attrs += static::$indexKeys;
        unset(
            $attrs['references'],
            $attrs['update'],
            $attrs['delete'],
            $attrs['constraint'],
            $attrs['deferrable'],
            $attrs['expression'],
        );

        if (!in_array($attrs['type'], static::$validIndexTypes, true)) {
            throw new DatabaseException(sprintf(
                'Invalid index type `%s` in index `%s` in table `%s`.',
                $attrs['type'],
                $name,
                $this->table,
            ));
        }
        $attrs['columns'] = (array)$attrs['columns'];
        foreach ($attrs['columns'] as $field) {
            if (empty($this->columns[$field])) {
                $msg = sprintf(
                    'Columns used in index `%s` in table `%s` must be added to the Table schema first. ' .
                    'The column `%s` was not found.',
                    $name,
                    $this->table,
                    $field,
                );
                throw new DatabaseException($msg);
            }
        }
        $attrs['name'] = $name;

        $this->indexes[$name] = new Index(...$attrs);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function indexes(): array
    {
        return array_keys($this->indexes);
    }

    /**
     * @inheritDoc
     */
    public function getIndex(string $name): ?array
    {
        if (!isset($this->indexes[$name])) {
            return null;
        }
        $index = $this->indexes[$name];
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
        $index = $this->indexes[$name] ?? null;
        if ($index === null) {
            $message = sprintf(
                'Table `%s` does not contain a index named `%s`.',
                $this->table,
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
        foreach ($this->constraints as $data) {
            if ($data->getType() === static::CONSTRAINT_PRIMARY) {
                return (array)$data->getColumns();
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function addConstraint(string $name, array|string $attrs): static
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$indexKeys);
        $attrs += static::$indexKeys;
        if ($attrs['constraint'] === null) {
            unset($attrs['constraint']);
        }

        if (!in_array($attrs['type'], static::$validConstraintTypes, true)) {
            throw new DatabaseException(sprintf(
                'Invalid constraint type `%s` in table `%s`.',
                $attrs['type'],
                $this->table,
            ));
        }
        if ($attrs['type'] !== self::CONSTRAINT_CHECK) {
            if (empty($attrs['columns'])) {
                throw new DatabaseException(sprintf(
                    'Constraints in table `%s` must have at least one column.',
                    $this->table,
                ));
            }
            $attrs['columns'] = (array)$attrs['columns'];
            foreach ($attrs['columns'] as $field) {
                if (empty($this->columns[$field])) {
                    $msg = sprintf(
                        'Columns used in constraints must be added to the Table schema first. ' .
                        'The column `%s` was not found in table `%s`.',
                        $field,
                        $this->table,
                    );
                    throw new DatabaseException($msg);
                }
            }
        }

        $attrs['name'] = $attrs['constraint'] ?? $name;
        unset($attrs['constraint'], $attrs['include']);

        $type = $attrs['type'] ?? null;
        if ($type === static::CONSTRAINT_FOREIGN) {
            $attrs = $this->checkForeignKey($attrs);
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
            $constraint = $this->constraints[$name] ?? null;
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

        $this->constraints[$name] = match ($type) {
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
    public function dropConstraint(string $name): static
    {
        if (isset($this->constraints[$name])) {
            unset($this->constraints[$name]);
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
        return array_any($this->columns, fn($column) => $column->getIdentity());
    }

    /**
     * Helper method to check/validate foreign keys.
     *
     * @param array<string, mixed> $attrs Attributes to set.
     * @return array<string, mixed>
     * @throws \Cake\Database\Exception\DatabaseException When foreign key definition is not valid.
     */
    protected function checkForeignKey(array $attrs): array
    {
        if (count($attrs['references']) < 2) {
            throw new DatabaseException('References must contain a table and column.');
        }
        if (!in_array($attrs['update'], static::$validForeignKeyActions)) {
            throw new DatabaseException(sprintf(
                'Update action is invalid. Must be one of %s',
                implode(',', static::$validForeignKeyActions),
            ));
        }
        if (!in_array($attrs['delete'], static::$validForeignKeyActions)) {
            throw new DatabaseException(sprintf(
                'Delete action is invalid. Must be one of %s',
                implode(',', static::$validForeignKeyActions),
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
        return array_keys($this->constraints);
    }

    /**
     * @inheritDoc
     */
    public function getConstraint(string $name): ?array
    {
        $constraint = $this->constraints[$name] ?? null;
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
        if (!isset($this->constraints[$name])) {
            $message = sprintf(
                'Table `%s` does not contain a constraint named `%s`.',
                $this->table,
                $name,
            );
            throw new DatabaseException($message);
        }

        return $this->constraints[$name];
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options): static
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function setTemporary(bool $temporary): static
    {
        $this->temporary = $temporary;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isTemporary(): bool
    {
        return $this->temporary;
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
        foreach (array_keys($this->columns) as $name) {
            $columns[] = $dialect->columnSql($this, $name);
        }
        foreach (array_keys($this->constraints) as $name) {
            $constraints[] = $dialect->constraintSql($this, $name);
        }
        foreach (array_keys($this->indexes) as $name) {
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
        $this->table = $data["\0*\0table"] ?? '';

        $columns = $data["\0*\0columns"] ?? [];
        foreach ($columns as $name => $column) {
            $name = (string)$name;
            if (is_array($column)) {
                $this->addColumn($name, $column);
            } else {
                $this->columns[$name] = $column;
            }
        }
        $indexes = $data["\0*\0indexes"] ?? [];
        foreach ($indexes as $name => $index) {
            $name = (string)$name;
            if (is_array($index)) {
                $this->addIndex($name, $index);
            } else {
                $this->indexes[$name] = $index;
            }
        }
        $constraints = $data["\0*\0constraints"] ?? [];
        foreach ($constraints as $name => $constraint) {
            $name = (string)$name;
            if (is_array($constraint)) {
                $this->addConstraint($name, $constraint);
            } else {
                $this->constraints[$name] = $constraint;
            }
        }
        $this->options = $data["\0*\0options"] ?? [];
        $this->typeMap = $data["\0*\0typeMap"] ?? [];
        $this->temporary = $data["\0*\0temporary"] ?? false;
    }

    /**
     * Returns an array of the table schema.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'table' => $this->table,
            'columns' => $this->columns,
            'indexes' => $this->indexes,
            'constraints' => $this->constraints,
            'options' => $this->options,
            'typeMap' => $this->typeMap,
            'temporary' => $this->temporary,
        ];
    }
}
