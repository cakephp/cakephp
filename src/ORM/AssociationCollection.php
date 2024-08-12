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
namespace Cake\ORM;

use ArrayIterator;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Locator\LocatorInterface;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use function Cake\Core\namespaceSplit;
use function Cake\Core\pluginSplit;

/**
 * A container/collection for association classes.
 *
 * Contains methods for managing associations, and
 * ordering operations around saving and deleting.
 *
 * @template-implements \IteratorAggregate<string, \Cake\ORM\Association>
 */
class AssociationCollection implements IteratorAggregate
{
    use AssociationsNormalizerTrait;
    use LocatorAwareTrait;

    /**
     * Stored associations
     *
     * @var array<string, \Cake\ORM\Association>
     */
    protected array $_items = [];

    /**
     * Constructor.
     *
     * Sets the default table locator for associations.
     * If no locator is provided, the global one will be used.
     *
     * @param \Cake\ORM\Locator\LocatorInterface|null $tableLocator Table locator instance.
     */
    public function __construct(?LocatorInterface $tableLocator = null)
    {
        if ($tableLocator !== null) {
            $this->_tableLocator = $tableLocator;
        }
    }

    /**
     * Add an association to the collection
     *
     * If the alias added contains a `.` the part preceding the `.` will be dropped.
     * This makes using plugins simpler as the Plugin.Class syntax is frequently used.
     *
     * @param string $alias The association alias
     * @param \Cake\ORM\Association $association The association to add.
     * @return \Cake\ORM\Association The association object being added.
     * @template T of \Cake\ORM\Association
     * @psalm-param T $association
     * @psalm-return T
     */
    public function add(string $alias, Association $association): Association
    {
        [, $alias] = pluginSplit($alias);

        return $this->_items[$alias] = $association;
    }

    /**
     * Creates and adds the Association object to this collection.
     *
     * @param string $className The name of association class.
     * @param string $associated The alias for the target table.
     * @param array<string, mixed> $options List of options to configure the association definition.
     * @return \Cake\ORM\Association
     * @throws \InvalidArgumentException
     * @template T of \Cake\ORM\Association
     * @psalm-param class-string<T> $className
     * @psalm-return T
     */
    public function load(string $className, string $associated, array $options = []): Association
    {
        $options += [
            'tableLocator' => $this->getTableLocator(),
        ];

        $association = new $className($associated, $options);

        return $this->add($association->getName(), $association);
    }

    /**
     * Fetch an attached association by name.
     *
     * @param string $alias The association alias to get.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    public function get(string $alias): ?Association
    {
        return $this->_items[$alias] ?? null;
    }

    /**
     * Fetch an association by property name.
     *
     * @param string $prop The property to find an association by.
     * @return \Cake\ORM\Association|null Either the association or null.
     */
    public function getByProperty(string $prop): ?Association
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
     * @return bool Whether the association exists.
     */
    public function has(string $alias): bool
    {
        return isset($this->_items[$alias]);
    }

    /**
     * Get the names of all the associations in the collection.
     *
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->_items);
    }

    /**
     * Get an array of associations matching a specific type.
     *
     * @param array<string>|string $class The type of associations you want.
     *   For example 'BelongsTo' or array like ['BelongsTo', 'HasOne']
     * @return array<\Cake\ORM\Association> An array of Association objects.
     * @since 3.5.3
     */
    public function getByType(array|string $class): array
    {
        $class = array_map('strtolower', (array)$class);

        $out = array_filter($this->_items, function ($assoc) use ($class) {
            [, $name] = namespaceSplit($assoc::class);

            return in_array(strtolower($name), $class, true);
        });

        return array_values($out);
    }

    /**
     * Drop/remove an association.
     *
     * Once removed the association will no longer be reachable
     *
     * @param string $alias The alias name.
     * @return void
     */
    public function remove(string $alias): void
    {
        unset($this->_items[$alias]);
    }

    /**
     * Remove all registered associations.
     *
     * Once removed associations will no longer be reachable
     *
     * @return void
     */
    public function removeAll(): void
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
     * @param array<string, mixed> $options The options for the save operation.
     * @return bool Success
     */
    public function saveParents(Table $table, EntityInterface $entity, array $associations, array $options = []): bool
    {
        if (!$associations) {
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
     * @param array<string, mixed> $options The options for the save operation.
     * @return bool Success
     */
    public function saveChildren(Table $table, EntityInterface $entity, array $associations, array $options): bool
    {
        if (!$associations) {
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
     * @param array<string, mixed> $options Original options
     * @param bool $owningSide Compared with association classes'
     *   isOwningSide method.
     * @return bool Success
     * @throws \InvalidArgumentException When an unknown alias is used.
     */
    protected function _saveAssociations(
        Table $table,
        EntityInterface $entity,
        array $associations,
        array $options,
        bool $owningSide
    ): bool {
        unset($options['associated']);
        foreach ($associations as $alias => $nested) {
            if (is_int($alias)) {
                $alias = $nested;
                $nested = [];
            }
            $relation = $this->get($alias);
            if (!$relation) {
                $msg = sprintf(
                    'Cannot save `%s`, it is not associated to `%s`.',
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
     * @param array<string, mixed> $nested Options for deeper associations
     * @param array<string, mixed> $options Original options
     * @return bool Success
     */
    protected function _save(
        Association $association,
        EntityInterface $entity,
        array $nested,
        array $options
    ): bool {
        if (!$entity->isDirty($association->getProperty())) {
            return true;
        }
        if ($nested) {
            $options = $nested + $options;
        }

        return (bool)$association->saveAssociated($entity, $options);
    }

    /**
     * Cascade a delete across the various associations.
     * Cascade first across associations for which cascadeCallbacks is true.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to delete associations for.
     * @param array<string, mixed> $options The options used in the delete operation.
     * @return bool
     */
    public function cascadeDelete(EntityInterface $entity, array $options): bool
    {
        $noCascade = [];
        foreach ($this->_items as $assoc) {
            if (!$assoc->getCascadeCallbacks()) {
                $noCascade[] = $assoc;
                continue;
            }
            $success = $assoc->cascadeDelete($entity, $options);
            if (!$success) {
                return false;
            }
        }

        foreach ($noCascade as $assoc) {
            $success = $assoc->cascadeDelete($entity, $options);
            if (!$success) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns an associative array of association names out a mixed
     * array. If true is passed, then it returns all association names
     * in this collection.
     *
     * @param array|string|bool $keys the list of association names to normalize
     * @return array
     */
    public function normalizeKeys(array|string|bool $keys): array
    {
        if ($keys === true) {
            $keys = $this->keys();
        }

        if (!$keys) {
            return [];
        }

        return $this->_normalizeAssociations($keys);
    }

    /**
     * Allow looping through the associations
     *
     * @return \Traversable<string, \Cake\ORM\Association>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_items);
    }
}
