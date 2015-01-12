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
 */
class EagerLoadable
{

    protected $_name;

    protected $_associations = [];

    protected $_instance;

    protected $_config = [];

    protected $_aliasPath;

    protected $_propertyPath;

    protected $_canBeJoined = false;

    protected $_forMatching;

    public function __construct($name, array $config = [])
    {
        $this->_name = $name;
        $allowed = [
            'associations', 'instance', 'config', 'canBeJoined',
            'aliasPath', 'propertyPath', 'forMatching'
        ];
        foreach ($allowed as $property) {
            if (isset($config[$property])) {
                $this->{'_' . $property} = $config[$property];
            }
        }
    }

    public function addAssociation($name, EagerLoadable $association)
    {
        $this->_associations[$name] = $association;
    }

    public function associations()
    {
        return $this->_associations;
    }

    public function instance($instance = null)
    {
        if ($instance === null) {
            return $this->_instance;
        }
        $this->_instance = $instance;
    }

    public function aliasPath($path = null)
    {
        if ($path === null) {
            return $this->_aliasPath;
        }
        $this->_aliasPath = $path;
    }

    public function propertyPath($path = null)
    {
        if ($path === null) {
            return $this->_propertyPath;
        }
        $this->_propertyPath = $path;
    }

    public function canBeJoined($possible = null)
    {
        if ($possible === null) {
            return $this->_canBeJoined;
        }
        $this->_canBeJoined = $possible;
    }

    public function config(array $config = null)
    {
        if ($config === null) {
            return $this->_config;
        }
        $this->_config = $config;
    }

    public function forMatching($matching = null)
    {
        if ($matching === null) {
            return $this->_forMatching;
        }
        $this->_forMatching = $matching;
    }

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
