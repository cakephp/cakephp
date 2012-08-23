<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP(tm) v 1.2.0.4525
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('AppModel', 'Model');

/**
 * A model used by TranslateBehavior to access the translation tables.
 *
 * @package Cake.Model
 */
class I18nModel extends AppModel {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'I18nModel';

/**
 * Table name
 *
 * @var string
 */
	public $useTable = 'i18n';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'field';

}
