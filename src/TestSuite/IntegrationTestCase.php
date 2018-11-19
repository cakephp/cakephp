<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

if (class_exists('PHPUnit_Runner_Version', false) && !interface_exists('PHPUnit\Exception', false)) {
    if (version_compare(\PHPUnit_Runner_Version::id(), '5.7', '<')) {
        trigger_error(sprintf('Your PHPUnit Version must be at least 5.7.0 to use CakePHP Testsuite, found %s', \PHPUnit_Runner_Version::id()), E_USER_ERROR);
    }
    class_alias('PHPUnit_Exception', 'PHPUnit\Exception');
}

/**
 * A test case class intended to make integration tests of
 * your controllers easier.
 *
 * This test class provides a number of helper methods and features
 * that make dispatching requests and checking their responses simpler.
 * It favours full integration tests over mock objects as you can test
 * more of your code easily and avoid some of the maintenance pitfalls
 * that mock objects create.
 *
 * @deprecated 3.7.0 Use Cake\TestSuite\IntegrationTestTrait instead
 */
abstract class IntegrationTestCase extends TestCase
{
    use IntegrationTestTrait;
}
