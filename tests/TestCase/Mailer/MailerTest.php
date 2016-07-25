<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\TestSuite\TestCase;
use TestApp\Mailer\TestMailer;

class MailerTest extends TestCase
{
    public function getMockForEmail($methods = [], $args = [])
    {
        return $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods((array)$methods)
            ->setConstructorArgs((array)$args)
            ->getMock();
    }

    public function testConstructor()
    {
        $mailer = new TestMailer();
        $this->assertInstanceOf('Cake\Mailer\Email', $mailer->getEmailForAssertion());
    }

    public function testReset()
    {
        $mailer = new TestMailer();
        $email = $mailer->getEmailForAssertion();

        $mailer->set(['foo' => 'bar']);
        $this->assertNotEquals($email->viewVars(), $mailer->reset()->getEmailForAssertion()->viewVars());
    }

    public function testGetName()
    {
        $result = (new TestMailer())->getName();
        $expected = 'Test';
        $this->assertEquals($expected, $result);
    }

    public function testLayout()
    {
        $result = (new TestMailer())->layout('foo');
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);
        $this->assertEquals('foo', $result->viewBuilder()->layout());
    }

    public function testProxies()
    {
        $email = $this->getMockForEmail('setHeaders');
        $email->expects($this->once())
            ->method('setHeaders')
            ->with(['X-Something' => 'nice']);
        $result = (new TestMailer($email))->setHeaders(['X-Something' => 'nice']);
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);

        $email = $this->getMockForEmail('addHeaders');
        $email->expects($this->once())
            ->method('addHeaders')
            ->with(['X-Something' => 'very nice', 'X-Other' => 'cool']);
        $result = (new TestMailer($email))->addHeaders(['X-Something' => 'very nice', 'X-Other' => 'cool']);
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);

        $email = $this->getMockForEmail('attachments');
        $email->expects($this->once())
            ->method('attachments')
            ->with([
                ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']
            ]);
        $result = (new TestMailer($email))->attachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']
        ]);
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);
    }

    public function testSet()
    {
        $email = $this->getMockForEmail('viewVars');
        $email->expects($this->once())
            ->method('viewVars')
            ->with(['key' => 'value']);
        $result = (new TestMailer($email))->set('key', 'value');
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);

        $email = $this->getMockForEmail('viewVars');
        $email->expects($this->once())
            ->method('viewVars')
            ->with(['key' => 'value']);
        $result = (new TestMailer($email))->set(['key' => 'value']);
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);
    }

    public function testSend()
    {
        $email = $this->getMockForEmail('send');
        $email->expects($this->any())
            ->method('send')
            ->will($this->returnValue([]));

        $mailer = $this->getMockBuilder('TestApp\Mailer\TestMailer')
            ->setMethods(['test'])
            ->setConstructorArgs([$email])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');

        $mailer->template('foobar');
        $mailer->send('test', ['foo', 'bar']);
        $this->assertEquals($mailer->template, 'foobar');
    }

    public function testSendWithUnsetTemplateDefaultsToActionName()
    {
        $email = $this->getMockForEmail('send');
        $email->expects($this->any())
            ->method('send')
            ->will($this->returnValue([]));

        $mailer = $this->getMockBuilder('TestApp\Mailer\TestMailer')
            ->setMethods(['test'])
            ->setConstructorArgs([$email])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');

        $mailer->send('test', ['foo', 'bar']);
        $this->assertEquals($mailer->template, 'test');
    }

    /**
     * test that initial email instance config is restored after email is sent.
     *
     * @return [type]
     */
    public function testDefaultProfileRestoration()
    {
        $email = $this->getMockForEmail('send', [['template' => 'cakephp']]);
        $email->expects($this->any())
            ->method('send')
            ->will($this->returnValue([]));

        $mailer = $this->getMockBuilder('TestApp\Mailer\TestMailer')
            ->setMethods(['test'])
            ->setConstructorArgs([$email])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');

        $mailer->template('test');
        $mailer->send('test', ['foo', 'bar']);
        $this->assertEquals($mailer->template, 'test');
        $this->assertEquals('cakephp', $mailer->viewBuilder()->template());
    }

    /**
     * @expectedException Cake\Mailer\Exception\MissingActionException
     * @expectedExceptionMessage Mail TestMailer::test() could not be found, or is not accessible.
     */
    public function testMissingActionThrowsException()
    {
        (new TestMailer())->send('test');
    }
}
