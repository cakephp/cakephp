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
namespace Cake\ORM;

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
    protected $_name;

    /**
     * A list of other associations to load from this level.
     *
     * @var \Cake\Orm\EagerLoadable[]
     */
    protected $_associations = [];

    /**
     * The Association class instance to use for loading the records.
     *
     * @var \Cake\ORM\Association
     */
    protected $_instance;

    /**
     * A list of options to pass to the association object for loading
     * the records.
     *
     * @var array
     */
    protected $_config = [];

    /**
     * A dotted separated string representing the path of associations
     * that should be followed to fetch this level.
     *
     * @var string
     */
    protected $_aliasPath;

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
     * @var string
     */
    protected $_propertyPath;

    /**
     * Whether or not this level can be fetched using a join.
     *
     * @var bool
     */
    protected $_canBeJoined = false;

    /**
     * Whether or not this level was meant for a "matching" fetch
     * operation
     *
     * @var bool
     */
    protected $_forMatching;

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
     * @var string
     */
    protected $_targetProperty;

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
     * @param array $config The list of properties to set.
     */
    public function __construct($name, array $config = [])
    {
        $this->_name = $name;
        $allowed = [
            'associations', 'instance', 'config', 'canBeJoined',
            'aliasPath', 'propertyPath', 'forMatching', 'targetProperty'
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
    public function addAssociation($name, EagerLoadable $association)
    {
        $this->_associations[$name] = $association;
    }

    /**
     * Returns the Association class instance to use for loading the records.
     *
     * @return array
     */
    public function associations()
    {
        return $this->_associations;
    }

    /**
     * Gets the Association class instance to use for loading the records.
     *
     * @return \Cake\ORM\Association|null
     */
    public function instance()
    {
        return $this->_instance;
    }

    /**
     * Gets a dot separated string representing the path of associations
     * that should be followed to fetch this level.
     *
     * @return string|null
     */
    public function aliasPath()
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
    public function propertyPath()
    {
        return $this->_propertyPath;
    }

    /**
     * Sets whether or not this level can be fetched using a join.
     *
     * @param bool $possible The value to set.
     * @return self
     */
    public function setCanBeJoined($possible)
    {
        $this->_canBeJoined = (bool)$possible;

        return $this;
    }

    /**
     * Gets whether or not this level can be fetched using a join.
     *
     * If called with arguments it sets the value.
     * As of 3.4.0 the setter part is deprecated, use setCanBeJoined() instead.
     *
     * @param bool|null $possible The value to set.
     * @return bool
     */
    public function canBeJoined($possible = null)
    {
        if ($possible !== null) {
            $this->setCanBeJoined($possible);
        }

        return $this->_canBeJoined;
    }

    /**
     * Sets the list of options to pass to the association object for loading
     * the records.
     *
     * @param array $config The value to set.
     * @return self
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
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Sets the list of options to pass to the association object for loading
     * the records.
     *
     * If called with no arguments it returns the current
     * value.
     *
     * @deprecated 3.4.0 Use setConfig()/getConfig() instead.
     * @param array|null $config The value to set.
     * @return array
     */
    public function config(array $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        }

        return $this->getConfig();
    }

    /**
     * Gets whether or not this level was meant for a
     * "matching" fetch operation.
     *
     * @return bool|null
     */
    public function forMatching()
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
    public function targetProperty()
    {
        return $this->_targetProperty;
    }

    /**
     * Returns a representation of this object that can be passed to
     * Cake\ORM\EagerLoader::contain()
     *
     * @return array
     */
    public function asContainArray()
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
                'config' => $config
            ]
        ];
    }
}
