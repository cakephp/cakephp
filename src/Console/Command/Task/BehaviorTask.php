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
namespace Cake\Console\Command\Task;

use Cake\Console\Command\Task\SimpleBakeTask;

/**
 * Behavior code generator.
 */
class BehaviorTask extends SimpleBakeTask {

/**
 * Task name used in path generation.
 *
 * @var string
 */
	public $pathFragment = 'Model/Behavior/';

/**
 * The name of the task used in menus and output.
 *
 * @var string
 */
	public $name = 'behavior';

/**
 * The suffix appended to generated class files.
 *
 * @var string
 */
	public $suffix = 'Behavior';

/**
 * Template name to use.
 *
 * @var string
 */
	public $template = 'behavior';

}
