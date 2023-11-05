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
use InvalidArgumentException;
use UnexpectedValueException;
use function Cake\Core\deprecationWarning;
use function Cake\Core\getTypeName;
use function Cake\Core\pluginSplit;

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
     * Use empty string to not use auto-loading on this object. Null auto-detects based on
     * controller name.
     *
     * @var string|null
     */
    protected $modelClass;

    /**
     * A list of overridden model factory functions.
     *
     * @var array<callable|\Cake\Datasource\Locator\LocatorInterface>
     */
    protected $_modelFactories = [];

    /**
     * The model type to use.
     *
     * @var string
     */
    protected $_modelType = 'Table';

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
        if ($this->modelClass === null) {
            $this->modelClass = $name;
        }
    }

    /**
     * Fetch or construct a model and set it to a property on this object.
     *
     * Uses a modelFactory based on `$modelType` to fetch and construct a `RepositoryInterface`
     * and set it as a property on the current object. The default `modelType`
     * can be defined with `setModelType()`.
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
     * @deprecated 4.3.0 Prefer `LocatorAwareTrait::fetchTable()` or `ModelAwareTrait::fetchModel()` instead.
     */
    public function loadModel(?string $modelClass = null, ?string $modelType = null): RepositoryInterface
    {
        $modelClass = $modelClass ?? $this->modelClass;
        if (empty($modelClass)) {
            throw new UnexpectedValueException('Default modelClass is empty');
        }
        $modelType = $modelType ?? $this->getModelType();

        $options = [];
        if (strpos($modelClass, '\\') === false) {
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
        if (!property_exists($this, $alias)) {
            deprecationWarning(
                '4.5.0 - Dynamic properties will be removed in PHP 8.2. ' .
                "Add `public \${$alias} = null;` to your class definition or use `#[AllowDynamicProperties]` attribute."
            );
        }

        if (isset($this->{$alias})) {
            return $this->{$alias};
        }

        $factory = $this->_modelFactories[$modelType] ?? FactoryLocator::get($modelType);
        if ($factory instanceof LocatorInterface) {
            $this->{$alias} = $factory->get($modelClass, $options);
        } else {
            $this->{$alias} = $factory($modelClass, $options);
        }

        if (!$this->{$alias}) {
            throw new MissingModelException([$modelClass, $modelType]);
        }

        return $this->{$alias};
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
        $modelClass = $modelClass ?? $this->modelClass;
        if (empty($modelClass)) {
            throw new UnexpectedValueException('Default modelClass is empty');
        }
        $modelType = $modelType ?? $this->getModelType();

        $options = [];
        if (strpos($modelClass, '\\') === false) {
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
    public function modelFactory(string $type, $factory): void
    {
        if (!$factory instanceof LocatorInterface && !is_callable($factory)) {
            throw new InvalidArgumentException(sprintf(
                '`$factory` must be an instance of Cake\Datasource\Locator\LocatorInterface or a callable.'
                . ' Got type `%s` instead.',
                getTypeName($factory)
            ));
        }

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
