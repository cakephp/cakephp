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

use Cake\Database\Exception\DatabaseException;

/**
 * Represents a single level in the associations tree to be eagerly loaded
 * for a specific query. This contains all the information required to
 * fetch the results from the database from an associations and all its children
 * levels.
 *
 * @internal
 */
class EagerLoadable
{
    /**
     * The name of the association to load.
     *
     * @var string
     */
    protected string $_name;

    /**
     * A list of other associations to load from this level.
     *
     * @var array<\Cake\ORM\EagerLoadable>
     */
    protected array $_associations = [];

    /**
     * The Association class instance to use for loading the records.
     *
     * @var \Cake\ORM\Association|null
     */
    protected ?Association $_instance = null;

    /**
     * A list of options to pass to the association object for loading
     * the records.
     *
     * @var array<string, mixed>
     */
    protected array $_config = [];

    /**
     * A dotted separated string representing the path of associations
     * that should be followed to fetch this level.
     *
     * @var string
     */
    protected string $_aliasPath;

    /**
     * A dotted separated string representing the path of entity properties
     * in which results for this level should be placed.
     *
     * For example, in the following nested property:
     *
     * ```
     *  $article->author->company->country
     * ```
     *
     * The property path of `country` will be `author.company`
     *
     * @var string|null
     */
    protected ?string $_propertyPath = null;

    /**
     * Whether this level can be fetched using a join.
     *
     * @var bool
     */
    protected bool $_canBeJoined = false;

    /**
     * Whether this level was meant for a "matching" fetch
     * operation
     *
     * @var bool|null
     */
    protected ?bool $_forMatching = null;

    /**
     * The property name where the association result should be nested
     * in the result.
     *
     * For example, in the following nested property:
     *
     * ```
     *  $article->author->company->country
     * ```
     *
     * The target property of `country` will be just `country`
     *
     * @var string|null
     */
    protected ?string $_targetProperty = null;

    /**
     * Constructor. The $config parameter accepts the following array
     * keys:
     *
     * - associations
     * - instance
     * - config
     * - canBeJoined
     * - aliasPath
     * - propertyPath
     * - forMatching
     * - targetProperty
     *
     * The keys maps to the settable properties in this class.
     *
     * @param string $name The Association name.
     * @param array<string, mixed> $config The list of properties to set.
     */
    public function __construct(string $name, array $config = [])
    {
        $this->_name = $name;
        $allowed = [
            'associations', 'instance', 'config', 'canBeJoined',
            'aliasPath', 'propertyPath', 'forMatching', 'targetProperty',
        ];
        foreach ($allowed as $property) {
            if (isset($config[$property])) {
                $this->{'_' . $property} = $config[$property];
            }
        }
    }

    /**
     * Adds a new association to be loaded from this level.
     *
     * @param string $name The association name.
     * @param \Cake\ORM\EagerLoadable $association The association to load.
     * @return void
     */
    public function addAssociation(string $name, EagerLoadable $association): void
    {
        $this->_associations[$name] = $association;
    }

    /**
     * Returns the Association class instance to use for loading the records.
     *
     * @return array<\Cake\ORM\EagerLoadable>
     */
    public function associations(): array
    {
        return $this->_associations;
    }

    /**
     * Gets the Association class instance to use for loading the records.
     *
     * @return \Cake\ORM\Association
     * @throws \Cake\Database\Exception\DatabaseException
     */
    public function instance(): Association
    {
        if ($this->_instance === null) {
            throw new DatabaseException('No instance set.');
        }

        return $this->_instance;
    }

    /**
     * Gets a dot separated string representing the path of associations
     * that should be followed to fetch this level.
     *
     * @return string
     */
    public function aliasPath(): string
    {
        return $this->_aliasPath;
    }

    /**
     * Gets a dot separated string representing the path of entity properties
     * in which results for this level should be placed.
     *
     * For example, in the following nested property:
     *
     * ```
     *  $article->author->company->country
     * ```
     *
     * The property path of `country` will be `author.company`
     *
     * @return string|null
     */
    public function propertyPath(): ?string
    {
        return $this->_propertyPath;
    }

    /**
     * Sets whether this level can be fetched using a join.
     *
     * @param bool $possible The value to set.
     * @return $this
     */
    public function setCanBeJoined(bool $possible)
    {
        $this->_canBeJoined = $possible;

        return $this;
    }

    /**
     * Gets whether this level can be fetched using a join.
     *
     * @return bool
     */
    public function canBeJoined(): bool
    {
        return $this->_canBeJoined;
    }

    /**
     * Sets the list of options to pass to the association object for loading
     * the records.
     *
     * @param array<string, mixed> $config The value to set.
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->_config = $config;

        return $this;
    }

    /**
     * Gets the list of options to pass to the association object for loading
     * the records.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->_config;
    }

    /**
     * Gets whether this level was meant for a
     * "matching" fetch operation.
     *
     * @return bool|null
     */
    public function forMatching(): ?bool
    {
        return $this->_forMatching;
    }

    /**
     * The property name where the result of this association
     * should be nested at the end.
     *
     * For example, in the following nested property:
     *
     * ```
     *  $article->author->company->country
     * ```
     *
     * The target property of `country` will be just `country`
     *
     * @return string|null
     */
    public function targetProperty(): ?string
    {
        return $this->_targetProperty;
    }

    /**
     * Returns a representation of this object that can be passed to
     * Cake\ORM\EagerLoader::contain()
     *
     * @return array<string, array>
     */
    public function asContainArray(): array
    {
        $associations = [];
        foreach ($this->_associations as $assoc) {
            $associations += $assoc->asContainArray();
        }
        $config = $this->_config;
        if ($this->_forMatching !== null) {
            $config = ['matching' => $this->_forMatching] + $config;
        }

        return [
            $this->_name => [
                'associations' => $associations,
                'config' => $config,
            ],
        ];
    }

    /**
     * Handles cloning eager loadables.
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->_associations as $i => $association) {
            $this->_associations[$i] = clone $association;
        }
    }
}
