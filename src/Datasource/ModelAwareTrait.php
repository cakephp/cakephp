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
namespace Cake\Datasource;

use Cake\Datasource\Exception\MissingModelException;
use Cake\Datasource\Locator\LocatorInterface;
use UnexpectedValueException;
use function Cake\Core\pluginSplit;

/**
 * Provides functionality for loading table classes
 * and other repositories onto properties of the host object.
 *
 * Example users of this trait are {@link \Cake\Controller\Controller} and
 * {@link \Cake\Command\Command}.
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
     * Use empty string to not use auto-loading on this object. Null auto-detects based on
     * controller name.
     *
     * @var string|null
     */
    protected ?string $modelClass = null;

    /**
     * A list of overridden model factory functions.
     *
     * @var array<callable|\Cake\Datasource\Locator\LocatorInterface>
     */
    protected array $_modelFactories = [];

    /**
     * The model type to use.
     *
     * @var string
     */
    protected string $_modelType = 'Table';

    /**
     * Set the modelClass property based on conventions.
     *
     * If the property is already set it will not be overwritten
     *
     * @param string $name Class name.
     * @return void
     */
    protected function _setModelClass(string $name): void
    {
        $this->modelClass ??= $name;
    }

    /**
     * Fetch or construct a model instance from a locator.
     *
     * Uses a modelFactory based on `$modelType` to fetch and construct a `RepositoryInterface`
     * and return it. The default `modelType` can be defined with `setModelType()`.
     *
     * Unlike `loadModel()` this method will *not* set an object property.
     *
     * If a repository provider does not return an object a MissingModelException will
     * be thrown.
     *
     * @param string|null $modelClass Name of model class to load. Defaults to $this->modelClass.
     *  The name can be an alias like `'Post'` or FQCN like `App\Model\Table\PostsTable::class`.
     * @param string|null $modelType The type of repository to load. Defaults to the getModelType() value.
     * @return \Cake\Datasource\RepositoryInterface The model instance created.
     * @throws \Cake\Datasource\Exception\MissingModelException If the model class cannot be found.
     * @throws \UnexpectedValueException If $modelClass argument is not provided
     *   and ModelAwareTrait::$modelClass property value is empty.
     */
    public function fetchModel(?string $modelClass = null, ?string $modelType = null): RepositoryInterface
    {
        $modelClass ??= $this->modelClass;
        if (!$modelClass) {
            throw new UnexpectedValueException('Default modelClass is empty');
        }
        $modelType ??= $this->getModelType();

        $options = [];
        if (!str_contains($modelClass, '\\')) {
            [, $alias] = pluginSplit($modelClass, true);
        } else {
            $options['className'] = $modelClass;
            /** @psalm-suppress PossiblyFalseOperand */
            $alias = substr(
                $modelClass,
                strrpos($modelClass, '\\') + 1,
                -strlen($modelType)
            );
            $modelClass = $alias;
        }

        $factory = $this->_modelFactories[$modelType] ?? FactoryLocator::get($modelType);
        if ($factory instanceof LocatorInterface) {
            $instance = $factory->get($modelClass, $options);
        } else {
            $instance = $factory($modelClass, $options);
        }
        if ($instance) {
            return $instance;
        }

        throw new MissingModelException([$modelClass, $modelType]);
    }

    /**
     * Override a existing callable to generate repositories of a given type.
     *
     * @param string $type The name of the repository type the factory function is for.
     * @param \Cake\Datasource\Locator\LocatorInterface|callable $factory The factory function used to create instances.
     * @return void
     */
    public function modelFactory(string $type, LocatorInterface|callable $factory): void
    {
        $this->_modelFactories[$type] = $factory;
    }

    /**
     * Get the model type to be used by this class
     *
     * @return string
     */
    public function getModelType(): string
    {
        return $this->_modelType;
    }

    /**
     * Set the model type to be used by this class
     *
     * @param string $modelType The model type
     * @return $this
     */
    public function setModelType(string $modelType)
    {
        $this->_modelType = $modelType;

        return $this;
    }
}
