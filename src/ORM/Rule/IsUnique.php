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
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;

/**
 * Checks that a list of fields from an entity are unique in the table
 */
class IsUnique
{
    /**
     * The list of fields to check
     *
     * @var string[]
     */
    protected $_fields;

    /**
     * Constructor.
     *
     * @param string[] $fields The list of fields to check uniqueness for
     */
    public function __construct(array $fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Performs the uniqueness check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     *   where the `repository` key is required.
     * @param array $options Options passed to the check,
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        if (!$entity->extract($this->_fields, true)) {
            return true;
        }

        $alias = $options['repository']->getAlias();
        $conditions = $this->_alias($alias, $entity->extract($this->_fields));
        if ($entity->isNew() === false) {
            $keys = (array)$options['repository']->getPrimaryKey();
            $keys = $this->_alias($alias, $entity->extract($keys));
            if (array_filter($keys, 'strlen')) {
                $conditions['NOT'] = $keys;
            }
        }

        return !$options['repository']->exists($conditions);
    }

    /**
     * Add a model alias to all the keys in a set of conditions.
     *
     * @param string $alias The alias to add.
     * @param array $conditions The conditions to alias.
     * @return array
     */
    protected function _alias(string $alias, array $conditions): array
    {
        $aliased = [];
        foreach ($conditions as $key => $value) {
            $aliased["$alias.$key IS"] = $value;
        }

        return $aliased;
    }
}
