<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

/**
 * A test case class intended to make integration tests of cake console commands
 * easier.
 *
 * @deprecated 3.7.0 Will be removed in 5.0.0. Use {@link \Cake\TestSuite\ConsoleIntegrationTestTrait} instead
 */
abstract class ConsoleIntegrationTestCase extends TestCase
{
    use ConsoleIntegrationTestTrait;
}
