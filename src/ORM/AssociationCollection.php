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
namespace Cake\ORM;

use ArrayIterator;
use Cake\Datasource\EntityInterface;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * A container/collection for association classes.
 *
 * Contains methods for managing associations, and
 * ordering operations around saving and deleting.
 */
class AssociationCollection implements IteratorAggregate
{

    use AssociationsNormalizerTrait;

    /**
     * Stored associations
     *
     * @var \Cake\ORM\Association[]
     */
    protected $_items = [];

    /**
     * Add an association to the collection
     *
     * If the alias added contains a `.` the part preceding the `.` will be dropped.
     * This makes using plugins simpler as the Plugin.Class syntax is frequently used.
     *
     * @param string $alias The association alias
     * @param \Cake\ORM\Association $association The association to add.
     * @return \Cake\ORM\Association The association object being added.
     */
    public function add($alias, Association $association)
    {
        list(, $alias) = pluginSplit($alias);

        return $this->_items[strtolower($alias)] = $association;
    }

    /**
     * Fetch an attached association by name.
     *
     * @param string $alias The association alias to get.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    public function get($alias)
    {
        $alias = strtolower($alias);
        if (isset($this->_items[$alias])) {
            return $this->_items[$alias];
        }

        return null;
    }

    /**
     * Fetch an association by property name.
     *
     * @param string $prop The property to find an association by.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    public function getByProperty($prop)
    {
        foreach ($this->_items as $assoc) {
            if ($assoc->getProperty() === $prop) {
                return $assoc;
            }
        }

        return null;
    }

    /**
     * Check for an attached association by name.
     *
     * @param string $alias The association alias to get.
     * @return bool Whether or not the association exists.
     */
    public function has($alias)
    {
        return isset($this->_items[strtolower($alias)]);
    }

    /**
     * Get the names of all the associations in the collection.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->_items);
    }

    /**
     * Get an array of associations matching a specific type.
     *
     * @param string|array $class The type of associations you want.
     *   For example 'BelongsTo' or array like ['BelongsTo', 'HasOne']
     * @return array An array of Association objects.
     */
    public function type($class)
    {
        $class = array_map('strtolower', (array)$class);

        $out = array_filter($this->_items, function ($assoc) use ($class) {
            list(, $name) = namespaceSplit(get_class($assoc));

            return in_array(strtolower($name), $class, true);
        });

        return array_values($out);
    }

    /**
     * Drop/remove an association.
     *
     * Once removed the association will not longer be reachable
     *
     * @param string $alias The alias name.
     * @return void
     */
    public function remove($alias)
    {
        unset($this->_items[strtolower($alias)]);
    }

    /**
     * Remove all registered associations.
     *
     * Once removed associations will not longer be reachable
     *
     * @return void
     */
    public function removeAll()
    {
        foreach ($this->_items as $alias => $object) {
            $this->remove($alias);
        }
    }

    /**
     * Save all the associations that are parents of the given entity.
     *
     * Parent associations include any association where the given table
     * is the owning side.
     *
     * @param \Cake\ORM\Table $table The table entity is for.
     * @param \Cake\Datasource\EntityInterface $entity The entity to save associated data for.
     * @param array $associations The list of associations to save parents from.
     *   associations not in this list will not be saved.
     * @param array $options The options for the save operation.
     * @return bool Success
     */
    public function saveParents(Table $table, EntityInterface $entity, $associations, array $options = [])
    {
        if (empty($associations)) {
            return true;
        }

        return $this->_saveAssociations($table, $entity, $associations, $options, false);
    }

    /**
     * Save all the associations that are children of the given entity.
     *
     * Child associations include any association where the given table
     * is not the owning side.
     *
     * @param \Cake\ORM\Table $table The table entity is for.
     * @param \Cake\Datasource\EntityInterface $entity The entity to save associated data for.
     * @param array $associations The list of associations to save children from.
     *   associations not in this list will not be saved.
     * @param array $options The options for the save operation.
     * @return bool Success
     */
    public function saveChildren(Table $table, EntityInterface $entity, array $associations, array $options)
    {
        if (empty($associations)) {
            return true;
        }

        return $this->_saveAssociations($table, $entity, $associations, $options, true);
    }

    /**
     * Helper method for saving an association's data.
     *
     * @param \Cake\ORM\Table $table The table the save is currently operating on
     * @param \Cake\Datasource\EntityInterface $entity The entity to save
     * @param array $associations Array of associations to save.
     * @param array $options Original options
     * @param bool $owningSide Compared with association classes'
     *   isOwningSide method.
     * @return bool Success
     * @throws \InvalidArgumentException When an unknown alias is used.
     */
    protected function _saveAssociations($table, $entity, $associations, $options, $owningSide)
    {
        unset($options['associated']);
        foreach ($associations as $alias => $nested) {
            if (is_int($alias)) {
                $alias = $nested;
                $nested = [];
            }
            $relation = $this->get($alias);
            if (!$relation) {
                $msg = sprintf(
                    'Cannot save %s, it is not associated to %s',
                    $alias,
                    $table->getAlias()
                );
                throw new InvalidArgumentException($msg);
            }
            if ($relation->isOwningSide($table) !== $owningSide) {
                continue;
            }
            if (!$this->_save($relation, $entity, $nested, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper method for saving an association's data.
     *
     * @param \Cake\ORM\Association $association The association object to save with.
     * @param \Cake\Datasource\EntityInterface $entity The entity to save
     * @param array $nested Options for deeper associations
     * @param array $options Original options
     * @return bool Success
     */
    protected function _save($association, $entity, $nested, $options)
    {
        if (!$entity->isDirty($association->getProperty())) {
            return true;
        }
        if (!empty($nested)) {
            $options = (array)$nested + $options;
        }

        return (bool)$association->saveAssociated($entity, $options);
    }

    /**
     * Cascade a delete across the various associations.
     * Cascade first across associations for which cascadeCallbacks is true.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to delete associations for.
     * @param array $options The options used in the delete operation.
     * @return void
     */
    public function cascadeDelete(EntityInterface $entity, array $options)
    {
        $noCascade = $this->_getNoCascadeItems($entity, $options);
        foreach ($noCascade as $assoc) {
            $assoc->cascadeDelete($entity, $options);
        }
    }

    /**
     * Returns items that have no cascade callback.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to delete associations for.
     * @param array $options The options used in the delete operation.
     * @return \Cake\ORM\Association[]
     */
    protected function _getNoCascadeItems($entity, $options)
    {
        $noCascade = [];
        foreach ($this->_items as $assoc) {
            if (!$assoc->getCascadeCallbacks()) {
                $noCascade[] = $assoc;
                continue;
            }
            $assoc->cascadeDelete($entity, $options);
        }

        return $noCascade;
    }

    /**
     * Returns an associative array of association names out a mixed
     * array. If true is passed, then it returns all association names
     * in this collection.
     *
     * @param bool|array $keys the list of association names to normalize
     * @return array
     */
    public function normalizeKeys($keys)
    {
        if ($keys === true) {
            $keys = $this->keys();
        }

        if (empty($keys)) {
            return [];
        }

        return $this->_normalizeAssociations($keys);
    }

    /**
     * Allow looping through the associations
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_items);
    }
}
