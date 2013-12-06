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
 * @since         CakePHP(tm) v 3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

/**
 * Provides functionality for loading table classes
 * and other repositories onto properties of the host object.
 *
 * Example users of this trait are Cake\Controller\Controller and 
 * Cake\Console\Shell.
 */
trait RepositoryAwareTrait {

/**
 * This object's primary model class name, the Inflector::singularize()'ed version of
 * the object's $name property.
 *
 * Example: For a object named 'Comments', the modelClass would be 'Comment'
 *
 * @var string
 */
	public $modelClass;

/**
 * This objects's repository key name, an underscored version of the objects's $modelClass property.
 *
 * Example: For an object named 'ArticleComments', the modelKey would be 'article_comment'
 *
 * @var string
 */
	public $modelKey;

/**
 * Set the modelClass and modelKey properties based on conventions.
 *
 * If the properties are already set they w
 */
	protected function _setModelClass($name) {
		$this->modelClass = Inflector::singularize($this->name);
		$this->modelKey = Inflector::underscore($this->modelClass);
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
 * @param string $modelClass Name of model class to load. Defaults to $this->modelClass
 * @param string $type The type of repository to load. Defaults to 'Table' which
 *   delegates to Cake\ORM\TableRegistry.
 * @return boolean True when single repository found and instance created.
 * @throws Cake\Error\MissingModelException if the model class cannot be found.
 */
	public function repository($modelClass = null, $type = 'Table') {
		if (isset($this->{$modelClass})) {
			return $this->{$modelClass};
		}

		if ($modelClass === null) {
			$modelClass = $this->modelClass;
		}

		list($plugin, $modelClass) = pluginSplit($modelClass, true);

		if ($type === 'Table') {
			$this->{$modelClass} = TableRegistry::get($plugin . $modelClass);
		}
		// TODO add other providers
		if (!$this->{$modelClass}) {
			throw new Error\MissingModelException($modelClass);
		}
		return true;
	}

}
