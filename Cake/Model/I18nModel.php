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
 * @since         CakePHP(tm) v 1.2.0.4525
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Model;

/**
 * A model used by TranslateBehavior to access the translation tables.
 *
 */
class I18nModel extends Model {

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
