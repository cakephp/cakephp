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
namespace Cake\Datasource;

use Cake\Datasource\Exception\MissingModelException;
use InvalidArgumentException;

/**
 * Provides functionality for loading table classes
 * and other repositories onto properties of the host object.
 *
 * Example users of this trait are Cake\Controller\Controller and
 * Cake\Console\Shell.
 */
trait ModelAwareTrait
{

    /**
     * This object's primary model class name. Should be a plural form.
     * CakePHP will not inflect the name.
     *
     * Example: For an object named 'Comments', the modelClass would be 'Comments'.
     * Plugin classes should use `Plugin.Comments` style names to correctly load
     * models from the correct plugin.
     *
     * @var string
     */
    public $modelClass;

    /**
     * A list of model factory functions.
     *
     * @var array
     */
    protected $_modelFactories = [];

    /**
     * Set the modelClass and modelKey properties based on conventions.
     *
     * If the properties are already set they will not be overwritten
     *
     * @param string $name Class name.
     * @return void
     */
    protected function _setModelClass($name)
    {
        if (empty($this->modelClass)) {
            $this->modelClass = $name;
        }
    }

    /**
     * Loads and constructs repository objects required by this object
     *
     * Typically used to load ORM Table objects as required. Can
     * also be used to load other types of repository objects your application uses.
     *
     * If a repository provider does not return an object a MissingModelException will
     * be thrown.
     *
     * @param string|null $modelClass Name of model class to load. Defaults to $this->modelClass
     * @param string $type The type of repository to load. Defaults to 'Table' which
     *   delegates to Cake\ORM\TableRegistry.
     * @return object The model instance created.
     * @throws \Cake\Datasource\Exception\MissingModelException If the model class cannot be found.
     * @throws \InvalidArgumentException When using a type that has not been registered.
     */
    public function loadModel($modelClass = null, $type = 'Table')
    {
        if ($modelClass === null) {
            $modelClass = $this->modelClass;
        }

        list($plugin, $alias) = pluginSplit($modelClass, true);

        if (isset($this->{$alias})) {
            return $this->{$alias};
        }

        if (!isset($this->_modelFactories[$type])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown repository type "%s". Make sure you register a type before trying to use it.',
                $type
            ));
        }
        $factory = $this->_modelFactories[$type];
        $this->{$alias} = $factory($modelClass);
        if (!$this->{$alias}) {
            throw new MissingModelException([$modelClass, $type]);
        }
        return $this->{$alias};
    }

    /**
     * Register a callable to generate repositories of a given type.
     *
     * @param string $type The name of the repository type the factory function is for.
     * @param callable $factory The factory function used to create instances.
     * @return void
     */
    public function modelFactory($type, callable $factory)
    {
        $this->_modelFactories[$type] = $factory;
    }
}
