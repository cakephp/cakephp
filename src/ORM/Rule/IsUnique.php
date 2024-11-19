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
use Cake\Utility\Hash;

/**
 * Checks that a list of fields from an entity are unique in the table
 */
class IsUnique
{
    /**
     * The list of fields to check
     *
     * @var array<string>
     */
    protected array $_fields;

    /**
     * The unique check options
     *
     * @var array<string, mixed>
     */
    protected array $_options = [
        'allowMultipleNulls' => true,
    ];

    /**
     * Constructor.
     *
     * ### Options
     *
     * - `allowMultipleNulls` Allows any field to have multiple null values. Defaults to true.
     *
     * @param array<string> $fields The list of fields to check uniqueness for
     * @param array<string, mixed> $options The options for unique checks.
     */
    public function __construct(array $fields, array $options = [])
    {
        $this->_fields = $fields;
        $this->_options = $options + $this->_options;
    }

    /**
     * Performs the uniqueness check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     *   where the `repository` key is required.
     * @param array<string, mixed> $options Options passed to the check,
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options): bool
    {
        if (!$entity->extract($this->_fields, true)) {
            return true;
        }

        $fields = $entity->extract($this->_fields);
        if ($this->_options['allowMultipleNulls'] && array_filter($fields, 'is_null')) {
            return true;
        }

        /** @var \Cake\ORM\Table $repository */
        $repository = $options['repository'];

        $alias = $repository->getAlias();
        $conditions = $this->_alias($alias, $fields);
        if ($entity->isNew() === false) {
            $keys = (array)$repository->getPrimaryKey();
            $keys = $this->_alias($alias, $entity->extract($keys));
            if (Hash::filter($keys)) {
                $conditions['NOT'] = $keys;
            }
        }

        return !$repository->exists($conditions);
    }

    /**
     * Add a model alias to all the keys in a set of conditions.
     *
     * @param string $alias The alias to add.
     * @param array $conditions The conditions to alias.
     * @return array<string, mixed>
     */
    protected function _alias(string $alias, array $conditions): array
    {
        $aliased = [];
        foreach ($conditions as $key => $value) {
            $aliased["{$alias}.{$key} IS"] = $value;
        }

        return $aliased;
    }
}
