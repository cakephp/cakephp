<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Core;

use Countable;
use IteratorAggregate;

/**
 * Plugin Registry Interface
 */
interface PluginRegistryInterface extends
    Countable,
    IteratorAggregate,
    ConsoleApplicationInterface,
    HttpApplicationInterface
{

}
