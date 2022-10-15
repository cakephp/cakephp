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
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Query;

use Cake\Database\ValueBinder;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;

/**
 * Update Query forward compatibility shim.
 */
class UpdateQuery extends Query
{
    /**
     * Type of this query (select, insert, update, delete).
     *
     * @var string
     */
    protected $_type = 'update';

    /**
     * @inheritDoc
     */
    public function sql(?ValueBinder $binder = null): string
    {
        if ($this->_type === 'update' && empty($this->_parts['update'])) {
            $repository = $this->getRepository();
            $this->update($repository->getTable());
        }

        return parent::sql($binder);
    }

    /**
     * @inheritDoc
     */
    public function delete(?string $table = null)
    {
        $this->_deprecatedMethod('delete()', 'Create your query with deleteQuery() instead.');

        return parent::delete($table);
    }

    /**
     * @inheritDoc
     */
    public function cache($key, $config = 'default')
    {
        $this->_deprecatedMethod('cache()', 'Use execute() instead.');

        return parent::cache($key, $config);
    }

    /**
     * @inheritDoc
     */
    public function all(): ResultSetInterface
    {
        $this->_deprecatedMethod('all()', 'Use execute() instead.');

        return parent::all();
    }

    /**
     * @inheritDoc
     */
    public function select($fields = [], bool $overwrite = false)
    {
        $this->_deprecatedMethod('select()', 'Create your query with selectQuery() instead.');

        return parent::select($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function distinct($on = [], $overwrite = false)
    {
        $this->_deprecatedMethod('distinct()');

        return parent::distinct($on, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function modifier($modifiers, $overwrite = false)
    {
        $this->_deprecatedMethod('modifier()');

        return parent::modifier($modifiers, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function group($fields, $overwrite = false)
    {
        $this->_deprecatedMethod('group()');

        return parent::group($fields, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function having($conditions = null, $types = [], $overwrite = false)
    {
        $this->_deprecatedMethod('having()');

        return parent::having($conditions, $types, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function andHaving($conditions, $types = [])
    {
        $this->_deprecatedMethod('andHaving()');

        return parent::andHaving($conditions, $types);
    }

    /**
     * @inheritDoc
     */
    public function page(int $num, ?int $limit = null)
    {
        $this->_deprecatedMethod('page()');

        return parent::page($num, $limit);
    }

    /**
     * @inheritDoc
     */
    public function offset($offset)
    {
        $this->_deprecatedMethod('offset()');

        return parent::offset($offset);
    }

    /**
     * @inheritDoc
     */
    public function union($query, $overwrite = false)
    {
        $this->_deprecatedMethod('union()');

        return parent::union($query, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function unionAll($query, $overwrite = false)
    {
        $this->_deprecatedMethod('union()');

        return parent::unionAll($query, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function insert(array $columns, array $types = [])
    {
        $this->_deprecatedMethod('insert()', 'Create your query with insertQuery() instead.');

        return parent::insert($columns, $types);
    }

    /**
     * @inheritDoc
     */
    public function into(string $table)
    {
        $this->_deprecatedMethod('into()', 'Use update() instead.');

        return parent::into($table);
    }

    /**
     * @inheritDoc
     */
    public function values($data)
    {
        $this->_deprecatedMethod('values()');

        return parent::values($data);
    }

    /**
     * @inheritDoc
     */
    public function from($tables = [], $overwrite = false)
    {
        $this->_deprecatedMethod('from()', 'Use update() instead.');

        return parent::from($tables, $overwrite);
    }

    /**
     * @inheritDoc
     */
    public function matching(string $assoc, ?callable $builder = null)
    {
        $this->_deprecatedMethod('matching()');

        return parent::matching($assoc, $builder);
    }

    /**
     * @inheritDoc
     */
    public function notMatching(string $assoc, ?callable $builder = null)
    {
        $this->_deprecatedMethod('notMatching()');

        return parent::notMatching($assoc, $builder);
    }

    /**
     * @inheritDoc
     */
    public function contain($associations, $override = false)
    {
        $this->_deprecatedMethod('contain()');

        return parent::contain($associations, $override);
    }

    /**
     * @inheritDoc
     */
    public function getContain(): array
    {
        $this->_deprecatedMethod('getContain()');

        return parent::getContain();
    }

    /**
     * @inheritDoc
     */
    public function find(string $finder, array $options = [])
    {
        $this->_deprecatedMethod('find()');

        return parent::find($finder, $options);
    }
}
