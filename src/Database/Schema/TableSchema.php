<?php
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
use Cake\Database\Exception;
use Cake\Database\Type;

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
    protected $_table;

    /**
     * Columns in the table.
     *
     * @var array
     */
    protected $_columns = [];

    /**
     * A map with columns to types
     *
     * @var array
     */
    protected $_typeMap = [];

    /**
     * Indexes in the table.
     *
     * @var array
     */
    protected $_indexes = [];

    /**
     * Constraints in the table.
     *
     * @var array
     */
    protected $_constraints = [];

    /**
     * Options for the table.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Whether or not the table is temporary
     *
     * @var bool
     */
    protected $_temporary = false;

    /**
     * Column length when using a `tiny` column type
     *
     * @var int
     */
    const LENGTH_TINY = 255;

    /**
     * Column length when using a `medium` column type
     *
     * @var int
     */
    const LENGTH_MEDIUM = 16777215;

    /**
     * Column length when using a `long` column type
     *
     * @var int
     */
    const LENGTH_LONG = 4294967295;

    /**
     * Valid column length that can be used with text type columns
     *
     * @var array
     */
    public static $columnLengths = [
        'tiny' => self::LENGTH_TINY,
        'medium' => self::LENGTH_MEDIUM,
        'long' => self::LENGTH_LONG,
    ];

    /**
     * The valid keys that can be used in a column
     * definition.
     *
     * @var array
     */
    protected static $_columnKeys = [
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
     * @var array
     */
    protected static $_columnExtras = [
        'string' => [
            'fixed' => null,
            'collate' => null,
        ],
        'text' => [
            'collate' => null,
        ],
        'tinyinteger' => [
            'unsigned' => null,
        ],
        'smallinteger' => [
            'unsigned' => null,
        ],
        'integer' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'biginteger' => [
            'unsigned' => null,
            'autoIncrement' => null,
        ],
        'decimal' => [
            'unsigned' => null,
        ],
        'float' => [
            'unsigned' => null,
        ],
    ];

    /**
     * The valid keys that can be used in an index
     * definition.
     *
     * @var array
     */
    protected static $_indexKeys = [
        'type' => null,
        'columns' => [],
        'length' => [],
        'references' => [],
        'update' => 'restrict',
        'delete' => 'restrict',
    ];

    /**
     * Names of the valid index types.
     *
     * @var array
     */
    protected static $_validIndexTypes = [
        self::INDEX_INDEX,
        self::INDEX_FULLTEXT,
    ];

    /**
     * Names of the valid constraint types.
     *
     * @var array
     */
    protected static $_validConstraintTypes = [
        self::CONSTRAINT_PRIMARY,
        self::CONSTRAINT_UNIQUE,
        self::CONSTRAINT_FOREIGN,
    ];

    /**
     * Names of the valid foreign key actions.
     *
     * @var array
     */
    protected static $_validForeignKeyActions = [
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
    const CONSTRAINT_PRIMARY = 'primary';

    /**
     * Unique constraint type
     *
     * @var string
     */
    const CONSTRAINT_UNIQUE = 'unique';

    /**
     * Foreign constraint type
     *
     * @var string
     */
    const CONSTRAINT_FOREIGN = 'foreign';

    /**
     * Index - index type
     *
     * @var string
     */
    const INDEX_INDEX = 'index';

    /**
     * Fulltext index type
     *
     * @var string
     */
    const INDEX_FULLTEXT = 'fulltext';

    /**
     * Foreign key cascade action
     *
     * @var string
     */
    const ACTION_CASCADE = 'cascade';

    /**
     * Foreign key set null action
     *
     * @var string
     */
    const ACTION_SET_NULL = 'setNull';

    /**
     * Foreign key no action
     *
     * @var string
     */
    const ACTION_NO_ACTION = 'noAction';

    /**
     * Foreign key restrict action
     *
     * @var string
     */
    const ACTION_RESTRICT = 'restrict';

    /**
     * Foreign key restrict default
     *
     * @var string
     */
    const ACTION_SET_DEFAULT = 'setDefault';

    /**
     * Constructor.
     *
     * @param string $table The table name.
     * @param array $columns The list of columns for the schema.
     */
    public function __construct($table, array $columns = [])
    {
        $this->_table = $table;
        foreach ($columns as $field => $definition) {
            $this->addColumn($field, $definition);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function name()
    {
        return $this->_table;
    }

    /**
     * {@inheritDoc}
     */
    public function addColumn($name, $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $valid = static::$_columnKeys;
        if (isset(static::$_columnExtras[$attrs['type']])) {
            $valid += static::$_columnExtras[$attrs['type']];
        }
        $attrs = array_intersect_key($attrs, $valid);
        $this->_columns[$name] = $attrs + $valid;
        $this->_typeMap[$name] = $this->_columns[$name]['type'];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeColumn($name)
    {
        unset($this->_columns[$name], $this->_typeMap[$name]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function columns()
    {
        return array_keys($this->_columns);
    }

    /**
     * Get column data in the table.
     *
     * @param string $name The column name.
     * @return array|null Column data or null.
     * @deprecated 3.5.0 Use getColumn() instead.
     */
    public function column($name)
    {
        deprecationWarning('TableSchema::column() is deprecated. Use TableSchema::getColumn() instead.');

        return $this->getColumn($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getColumn($name)
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }
        $column = $this->_columns[$name];
        unset($column['baseType']);

        return $column;
    }

    /**
     * Sets the type of a column, or returns its current type
     * if none is passed.
     *
     * @param string $name The column to get the type of.
     * @param string|null $type The type to set the column to.
     * @return string|null Either the column type or null.
     * @deprecated 3.5.0 Use setColumnType()/getColumnType() instead.
     */
    public function columnType($name, $type = null)
    {
        deprecationWarning('TableSchema::columnType() is deprecated. Use TableSchema::setColumnType() or TableSchema::getColumnType() instead.');

        if ($type !== null) {
            $this->setColumnType($name, $type);
        }

        return $this->getColumnType($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getColumnType($name)
    {
        if (!isset($this->_columns[$name])) {
            return null;
        }

        return $this->_columns[$name]['type'];
    }

    /**
     * {@inheritDoc}
     */
    public function setColumnType($name, $type)
    {
        if (!isset($this->_columns[$name])) {
            return $this;
        }

        $this->_columns[$name]['type'] = $type;
        $this->_typeMap[$name] = $type;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasColumn($name)
    {
        return isset($this->_columns[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function baseColumnType($column)
    {
        if (isset($this->_columns[$column]['baseType'])) {
            return $this->_columns[$column]['baseType'];
        }

        $type = $this->getColumnType($column);

        if ($type === null) {
            return null;
        }

        if (Type::getMap($type)) {
            $type = Type::build($type)->getBaseType();
        }

        return $this->_columns[$column]['baseType'] = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function typeMap()
    {
        return $this->_typeMap;
    }

    /**
     * {@inheritDoc}
     */
    public function isNullable($name)
    {
        if (!isset($this->_columns[$name])) {
            return true;
        }

        return ($this->_columns[$name]['null'] === true);
    }

    /**
     * {@inheritDoc}
     */
    public function defaultValues()
    {
        $defaults = [];
        foreach ($this->_columns as $name => $data) {
            if (!array_key_exists('default', $data)) {
                continue;
            }
            if ($data['default'] === null && $data['null'] !== true) {
                continue;
            }
            $defaults[$name] = $data['default'];
        }

        return $defaults;
    }

    /**
     * {@inheritDoc}
     * @throws \Cake\Database\Exception
     */
    public function addIndex($name, $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$_indexKeys);
        $attrs += static::$_indexKeys;
        unset($attrs['references'], $attrs['update'], $attrs['delete']);

        if (!in_array($attrs['type'], static::$_validIndexTypes, true)) {
            throw new Exception(sprintf('Invalid index type "%s" in index "%s" in table "%s".', $attrs['type'], $name, $this->_table));
        }
        if (empty($attrs['columns'])) {
            throw new Exception(sprintf('Index "%s" in table "%s" must have at least one column.', $name, $this->_table));
        }
        $attrs['columns'] = (array)$attrs['columns'];
        foreach ($attrs['columns'] as $field) {
            if (empty($this->_columns[$field])) {
                $msg = sprintf(
                    'Columns used in index "%s" in table "%s" must be added to the Table schema first. ' .
                    'The column "%s" was not found.',
                    $name,
                    $this->_table,
                    $field
                );
                throw new Exception($msg);
            }
        }
        $this->_indexes[$name] = $attrs;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function indexes()
    {
        return array_keys($this->_indexes);
    }

    /**
     * Read information about an index based on name.
     *
     * @param string $name The name of the index.
     * @return array|null Array of index data, or null
     * @deprecated 3.5.0 Use getIndex() instead.
     */
    public function index($name)
    {
        deprecationWarning('TableSchema::index() is deprecated. Use TableSchema::getIndex() instead.');

        return $this->getIndex($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getIndex($name)
    {
        if (!isset($this->_indexes[$name])) {
            return null;
        }

        return $this->_indexes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function primaryKey()
    {
        foreach ($this->_constraints as $name => $data) {
            if ($data['type'] === static::CONSTRAINT_PRIMARY) {
                return $data['columns'];
            }
        }

        return [];
    }

    /**
     * {@inheritDoc}
     * @throws \Cake\Database\Exception
     */
    public function addConstraint($name, $attrs)
    {
        if (is_string($attrs)) {
            $attrs = ['type' => $attrs];
        }
        $attrs = array_intersect_key($attrs, static::$_indexKeys);
        $attrs += static::$_indexKeys;
        if (!in_array($attrs['type'], static::$_validConstraintTypes, true)) {
            throw new Exception(sprintf('Invalid constraint type "%s" in table "%s".', $attrs['type'], $this->_table));
        }
        if (empty($attrs['columns'])) {
            throw new Exception(sprintf('Constraints in table "%s" must have at least one column.', $this->_table));
        }
        $attrs['columns'] = (array)$attrs['columns'];
        foreach ($attrs['columns'] as $field) {
            if (empty($this->_columns[$field])) {
                $msg = sprintf(
                    'Columns used in constraints must be added to the Table schema first. ' .
                    'The column "%s" was not found in table "%s".',
                    $field,
                    $this->_table
                );
                throw new Exception($msg);
            }
        }

        if ($attrs['type'] === static::CONSTRAINT_FOREIGN) {
            $attrs = $this->_checkForeignKey($attrs);

            if (isset($this->_constraints[$name])) {
                $this->_constraints[$name]['columns'] = array_unique(array_merge(
                    $this->_constraints[$name]['columns'],
                    $attrs['columns']
                ));

                if (isset($this->_constraints[$name]['references'])) {
                    $this->_constraints[$name]['references'][1] = array_unique(array_merge(
                        (array)$this->_constraints[$name]['references'][1],
                        [$attrs['references'][1]]
                    ));
                }

                return $this;
            }
        } else {
            unset($attrs['references'], $attrs['update'], $attrs['delete']);
        }

        $this->_constraints[$name] = $attrs;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dropConstraint($name)
    {
        if (isset($this->_constraints[$name])) {
            unset($this->_constraints[$name]);
        }

        return $this;
    }

    /**
     * Check whether or not a table has an autoIncrement column defined.
     *
     * @return bool
     */
    public function hasAutoincrement()
    {
        foreach ($this->_columns as $column) {
            if (isset($column['autoIncrement']) && $column['autoIncrement']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to check/validate foreign keys.
     *
     * @param array $attrs Attributes to set.
     * @return array
     * @throws \Cake\Database\Exception When foreign key definition is not valid.
     */
    protected function _checkForeignKey($attrs)
    {
        if (count($attrs['references']) < 2) {
            throw new Exception('References must contain a table and column.');
        }
        if (!in_array($attrs['update'], static::$_validForeignKeyActions)) {
            throw new Exception(sprintf('Update action is invalid. Must be one of %s', implode(',', static::$_validForeignKeyActions)));
        }
        if (!in_array($attrs['delete'], static::$_validForeignKeyActions)) {
            throw new Exception(sprintf('Delete action is invalid. Must be one of %s', implode(',', static::$_validForeignKeyActions)));
        }

        return $attrs;
    }

    /**
     * {@inheritDoc}
     */
    public function constraints()
    {
        return array_keys($this->_constraints);
    }

    /**
     * Read information about a constraint based on name.
     *
     * @param string $name The name of the constraint.
     * @return array|null Array of constraint data, or null
     * @deprecated 3.5.0 Use getConstraint() instead.
     */
    public function constraint($name)
    {
        deprecationWarning('TableSchema::constraint() is deprecated. Use TableSchema::getConstraint() instead.');

        return $this->getConstraint($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraint($name)
    {
        if (!isset($this->_constraints[$name])) {
            return null;
        }

        return $this->_constraints[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get/set the options for a table.
     *
     * Table options allow you to set platform specific table level options.
     * For example the engine type in MySQL.
     *
     * @deprecated 3.4.0 Use setOptions()/getOptions() instead.
     * @param array|null $options The options to set, or null to read options.
     * @return $this|array Either the TableSchema instance, or an array of options when reading.
     */
    public function options($options = null)
    {
        deprecationWarning('TableSchema::options() is deprecated. Use TableSchema::setOptions() or TableSchema::getOptions() instead.');

        if ($options !== null) {
            return $this->setOptions($options);
        }

        return $this->getOptions();
    }

    /**
     * {@inheritDoc}
     */
    public function setTemporary($temporary)
    {
        $this->_temporary = (bool)$temporary;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isTemporary()
    {
        return $this->_temporary;
    }

    /**
     * Get/Set whether the table is temporary in the database
     *
     * @deprecated 3.4.0 Use setTemporary()/isTemporary() instead.
     * @param bool|null $temporary whether or not the table is to be temporary
     * @return $this|bool Either the TableSchema instance, the current temporary setting
     */
    public function temporary($temporary = null)
    {
        deprecationWarning(
            'TableSchema::temporary() is deprecated. ' .
            'Use TableSchema::setTemporary()/isTemporary() instead.'
        );
        if ($temporary !== null) {
            return $this->setTemporary($temporary);
        }

        return $this->isTemporary();
    }

    /**
     * {@inheritDoc}
     */
    public function createSql(Connection $connection)
    {
        $dialect = $connection->getDriver()->schemaDialect();
        $columns = $constraints = $indexes = [];
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
     * {@inheritDoc}
     */
    public function dropSql(Connection $connection)
    {
        $dialect = $connection->getDriver()->schemaDialect();

        return $dialect->dropTableSql($this);
    }

    /**
     * {@inheritDoc}
     */
    public function truncateSql(Connection $connection)
    {
        $dialect = $connection->getDriver()->schemaDialect();

        return $dialect->truncateTableSql($this);
    }

    /**
     * {@inheritDoc}
     */
    public function addConstraintSql(Connection $connection)
    {
        $dialect = $connection->getDriver()->schemaDialect();

        return $dialect->addConstraintSql($this);
    }

    /**
     * {@inheritDoc}
     */
    public function dropConstraintSql(Connection $connection)
    {
        $dialect = $connection->getDriver()->schemaDialect();

        return $dialect->dropConstraintSql($this);
    }

    /**
     * Returns an array of the table schema.
     *
     * @return array
     */
    public function __debugInfo()
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

// @deprecated 3.4.0 Add backwards compat alias.
class_alias('Cake\Database\Schema\TableSchema', 'Cake\Database\Schema\Table');
