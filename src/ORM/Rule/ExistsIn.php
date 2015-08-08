<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Association;

/**
 * Checks that the value provided in a field exists as the primary key of another
 * table.
 */
class ExistsIn
{

    /**
     * The list of fields to check
     *
     * @var array
     */
    protected $_fields;

    /**
     * The repository where the field will be looked for
     *
     * @var array
     */
    protected $_repository;

    /**
     * Constructor.
     *
     * @param string|array $fields The field or fields to check existence as primary key.
     * @param object|string $repository The repository where the field will be looked for,
     * or the association name for the repository.
     */
    public function __construct($fields, $repository)
    {
        $this->_fields = (array)$fields;
        $this->_repository = $repository;
    }

    /**
     * Performs the existence check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     * @param array $options Options passed to the check,
     * where the `repository` key is required.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        if (is_string($this->_repository)) {
            $this->_repository = $options['repository']->association($this->_repository);
        }

        $source = !empty($options['repository']) ? $options['repository'] : $this->_repository;
        $source = $source instanceof Association ? $source->source() : $source;
        $target = $this->_repository instanceof Association ?
            $this->_repository->target() :
            $this->_repository;

        if (!empty($options['_sourceTable']) && $target === $options['_sourceTable']) {
            return true;
        }

        if (!$entity->extract($this->_fields, true)) {
            return true;
        }

        $nulls = 0;
        $schema = $source->schema();
        foreach ($this->_fields as $field) {
            if ($schema->column($field) && $schema->isNullable($field) && $entity->get($field) === null) {
                $nulls++;
            }
        }
        if ($nulls === count($this->_fields)) {
            return true;
        }

        $primary = array_map(
            [$this->_repository, 'aliasField'],
            (array)$this->_repository->primaryKey()
        );
        $conditions = array_combine(
            $primary,
            $entity->extract($this->_fields)
        );
        return $this->_repository->exists($conditions);
    }
}
