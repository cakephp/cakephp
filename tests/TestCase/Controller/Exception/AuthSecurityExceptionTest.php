<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.2.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Exception;

use Cake\Controller\Exception\AuthSecurityException;
use Cake\TestSuite\TestCase;

/**
 * AuthSecurityException Test class
 *
 */
class AuthSecurityExceptionTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->authSecurityException = new AuthSecurityException;
    }

    /**
     * Test the getType() function.
     *
     * @return void
     */
    public function testGetType()
    {
        $this->assertEquals(
            'auth',
            $this->authSecurityException->getType(),
            '::getType should always return the type of `auth`.'
        );
    }
}
