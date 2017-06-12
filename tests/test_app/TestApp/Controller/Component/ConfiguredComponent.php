<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * A test component that makes a copy of the configuration.
 */
class ConfiguredComponent extends Component
{
    /**
     * @var array
     */
    public $configCopy;

    /**
     * components property
     *
     * @var array
     */
    public $components = [];

    /**
     * Constructor
     *
     * @param ComponentRegistry $registry A ComponentRegistry this component can use to lazy load its components
     * @param array $config Array of configuration settings.
     * @param array $components Array of child components.
     */
    public function __construct(ComponentRegistry $registry, array $config, array $components = [])
    {
        $this->components = $components;

        parent::__construct($registry, $config);
    }

    /**
     * @param array $config
     */
    public function initialize(array $config)
    {
        $this->configCopy = $config;

        parent::initialize($config);
    }
}
