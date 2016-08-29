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
 * @since         3.3.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Mailer\Email;
use Cake\Mailer\Transport\DebugTransport;
use Cake\TestSuite\EmailAssertTrait;
use Cake\TestSuite\TestCase;
use TestApp\Mailer\TestUserMailer;

class EmailAssertTraitTest extends TestCase
{

    use EmailAssertTrait;

    public function setUp()
    {
        parent::setUp();
        Email::configTransport('debug', ['className' => DebugTransport::class]);
    }

    public function tearDown()
    {
        parent::tearDown();
        Email::dropTransport('debug');
    }

    public function testFunctional()
    {
        $mailer = $this->getMockForMailer(TestUserMailer::class);
        $email = $mailer->getEmailForAssertion();
        $this->assertSame($this->_email, $email);

        $mailer->invite('lorenzo@cakephp.org');
        $this->assertEmailSubject('CakePHP');
        $this->assertEmailFrom('jadb@cakephp.org');
        $this->assertEmailTo('lorenzo@cakephp.org');
        $this->assertEmailToContains('lorenzo@cakephp.org');
        $this->assertEmailToContains('lorenzo@cakephp.org', 'lorenzo@cakephp.org');
        $this->assertEmailCcContains('markstory@cakephp.org');
        $this->assertEmailCcContains('admad@cakephp.org', 'Adnan');
        $this->assertEmailBccContains('dereuromark@cakephp.org');
        $this->assertEmailBccContains('antograssiot@cakephp.org');
        $this->assertEmailTextMessageContains('Hello lorenzo@cakephp.org');
        $this->assertEmailAttachmentsContains('TestUserMailer.php');
        $this->assertEmailAttachmentsContains('TestMailer.php', [
            'file' => dirname(dirname(__DIR__)) . DS . 'test_app' . DS . 'TestApp' . DS . 'Mailer' . DS . 'TestMailer.php',
            'mimetype' => 'application/octet-stream',
        ]);
    }
}
