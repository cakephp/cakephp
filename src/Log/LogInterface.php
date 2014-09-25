<?php
/**
 * CakeLogInterface
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Log;

use Psr\Log\LoggerInterface;

/**
 * LogInterface is the interface that should be implemented
 * by all classes that are going to be used as Log streams.
 *
 * @deprecated 3.0.0-beta1 Will be removed in 3.0.0 stable.
 */
interface LogInterface extends LoggerInterface {

}
