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

use Cake\Controller\Exception\SecurityException;
use Cake\TestSuite\TestCase;

/**
 * SecurityException Test class
 */
class SecurityExceptionTest extends TestCase
{
    /**
     * @var \Cake\Controller\Exception\SecurityException
     */
    protected $securityException;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->securityException = new SecurityException();
    }

    /**
     * Test the getType() function.
     */
    public function testGetType(): void
    {
        $this->assertSame(
            'secure',
            $this->securityException->getType(),
            '::getType should always return the type of `secure`.'
        );
    }

    /**
     * Test the setMessage() function.
     */
    public function testSetMessage(): void
    {
        $sampleMessage = 'foo';
        $this->securityException->setMessage($sampleMessage);
        $this->assertSame(
            $sampleMessage,
            $this->securityException->getMessage(),
            '::getMessage should always return the message set.'
        );
    }

    /**
     * Test the setReason() and corresponding getReason() function.
     */
    public function testSetGetReason(): void
    {
        $sampleReason = 'canary';
        $this->securityException->setReason($sampleReason);
        $this->assertSame(
            $sampleReason,
            $this->securityException->getReason(),
            '::getReason should always return the reason set.'
        );
    }
}
