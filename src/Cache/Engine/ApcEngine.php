<?php
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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache\Engine;

// @deprecated 3.6.0 Add backwards compat alias.
class_alias('Cake\Cache\Engine\ApcuEngine', 'Cake\Cache\Engine\ApcEngine');

deprecationWarning('Use Cake\Cache\Engine\ApcuEngine instead of Cake\Cache\Engine\ApcEngine.');
