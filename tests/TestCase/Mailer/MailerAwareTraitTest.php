<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\MailerAwareTrait;
use Cake\TestSuite\TestCase;

/**
 * Testing stub.
 */
class Stub
{

    use MailerAwareTrait;
}

/**
 * MailerAwareTrait test case
 */
class MailerAwareTraitTest extends TestCase
{

    /**
     * Test getMailer
     *
     * @return void
     */
    public function testGetMailer()
    {
        $originalAppNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');
        $stub = new Stub();
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $stub->getMailer('Test'));
        Configure::write('App.namespace', $originalAppNamespace);
    }

    /**
     * Test exception thrown by getMailer.
     *
     * @expectedException \Cake\Mailer\Exception\MissingMailerException
     * @expectedExceptionMessage Mailer class "Test" could not be found.
     */
    public function testGetMailerThrowsException()
    {
        $stub = new Stub();
        $stub->getMailer('Test');
    }
}
