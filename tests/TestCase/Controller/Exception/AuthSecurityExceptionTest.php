<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.2.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Exception;

use Cake\Controller\Exception\AuthSecurityException;
use Cake\TestSuite\TestCase;

/**
 * AuthSecurityException Test class
 */
class AuthSecurityExceptionTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->authSecurityException = new AuthSecurityException();
    }

    /**
     * Test the getType() function.
     *
     * @return void
     */
    public function testGetType(): void
    {
        $this->assertSame(
            'auth',
            $this->authSecurityException->getType(),
            '::getType should always return the type of `auth`.'
        );
    }
}
