<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\TestSuite\TestCase;
use RuntimeException;
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
        $this->assertEquals('foo', $result->getLayout());
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

    /**
     * Test that get/set methods can be proxied.
     *
     * @return void
     */
    public function testGetSetProxies()
    {
        $mailer = new TestMailer();
        $result = $mailer->setLayout('custom')
            ->setTo('test@example.com')
            ->setCc('cc@example.com');
        $this->assertSame($result, $mailer);

        $this->assertSame(['test@example.com' => 'test@example.com'], $result->getTo());
        $this->assertSame(['cc@example.com' => 'cc@example.com'], $result->getCc());
    }

    public function testSet()
    {
        $email = $this->getMockForEmail('setViewVars');
        $email->expects($this->once())
            ->method('setViewVars')
            ->with(['key' => 'value']);
        $result = (new TestMailer($email))->set('key', 'value');
        $this->assertInstanceOf('TestApp\Mailer\TestMailer', $result);

        $email = $this->getMockForEmail('setViewVars');
        $email->expects($this->once())
            ->method('setViewVars')
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
     * Test that mailers call reset() when send fails
     */
    public function testSendFailsEmailIsReset()
    {
        $email = $this->getMockForEmail(['send', 'reset']);
        $email->expects($this->once())
            ->method('send')
            ->will($this->throwException(new RuntimeException('kaboom')));

        $mailer = $this->getMockBuilder('TestApp\Mailer\TestMailer')
            ->setMethods(['welcome', 'reset'])
            ->setConstructorArgs([$email])
            ->getMock();

        // Mailer should be reset even if sending fails.
        $mailer->expects($this->once())
            ->method('reset');

        try {
            $mailer->send('welcome', ['foo', 'bar']);
            $this->fail('Exception should bubble up.');
        } catch (RuntimeException $e) {
            $this->assertTrue(true, 'Exception was raised');
        }
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
     */
    public function testMissingActionThrowsException()
    {
        $this->expectException(\Cake\Mailer\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Mail TestMailer::test() could not be found, or is not accessible.');
        (new TestMailer())->send('test');
    }
}
