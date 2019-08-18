<?php
declare(strict_types=1);

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

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Mailer;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use RuntimeException;
use TestApp\Mailer\TestMailer;

class MailerTest extends TestCase
{
    /**
     * @var array
     */
    protected $transports = [];

    /**
     * @var \Cake\Mailer\Mailer
     */
    protected $mailer;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transports = [
            'debug' => [
                'className' => 'Debug',
            ],
            'badClassName' => [
                'className' => 'TestFalse',
            ],
        ];

        TransportFactory::setConfig($this->transports);

        $this->mailer = new TestMailer();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        TransportFactory::drop('debug');
        TransportFactory::drop('badClassName');
    }

    /**
     * @param array $methods
     * @param array $args
     * @return \Cake\Mailer\Email|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockForEmail($methods = [], $args = [])
    {
        return $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods((array)$methods)
            ->setConstructorArgs((array)$args)
            ->getMock();
    }

    /**
     * @return void
     */
    public function testConstructor()
    {
        $mailer = new TestMailer();
        $this->assertInstanceOf('Cake\Mailer\Email', $mailer->getEmailForAssertion());
    }

    /**
     * testTransport method
     *
     * @return void
     */
    public function testTransport()
    {
        $result = $this->mailer->setTransport('debug');
        $this->assertSame($this->mailer, $result);

        $result = $this->mailer->getTransport();
        $this->assertInstanceOf(DebugTransport::class, $result);

        $instance = $this->getMockBuilder(DebugTransport::class)->getMock();
        $this->mailer->setTransport($instance);
        $this->assertSame($instance, $this->mailer->getTransport());
    }

    /**
     * Test that using unknown transports fails.
     *
     */
    public function testTransportInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "Invalid" transport configuration does not exist');
        $this->mailer->setTransport('Invalid');
    }

    /**
     * Test that using classes with no send method fails.
     *
     */
    public function testTransportInstanceInvalid()
    {
        $this->expectException(Exception::class);
        $this->mailer->setTransport(new \StdClass());
    }

    /**
     * Test that using unknown transports fails.
     *
     */
    public function testTransportTypeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value passed for the "$name" argument must be either a string, or an object, integer given.');
        $this->mailer->setTransport(123);
    }

    /**
     * Test reading/writing configuration profiles.
     *
     * @return void
     */
    public function testConfig()
    {
        $settings = [
            'to' => 'mark@example.com',
            'from' => 'noreply@example.com',
        ];
        Mailer::setConfig('test', $settings);
        $this->assertEquals($settings, Mailer::getConfig('test'), 'Should be the same.');

        $mailer = new TestMailer('test');
        $this->assertContains($settings['to'], $mailer->getTo());
    }

    /**
     * Test that exceptions are raised on duplicate config set.
     *
     * @return void
     */
    public function testConfigErrorOnDuplicate()
    {
        $this->expectException(\BadMethodCallException::class);
        $settings = [
            'to' => 'mark@example.com',
            'from' => 'noreply@example.com',
        ];
        Mailer::setConfig('test', $settings);
        Mailer::setConfig('test', $settings);
    }

    /**
     * test profile method
     *
     * @return void
     */
    public function testSetProfile()
    {
        $config = ['to' => 'foo@bar.com'];
        $this->mailer->setProfile($config);
        $this->assertSame(['foo@bar.com' => 'foo@bar.com'], $this->mailer->getTo());
    }

    /**
     * test that default profile is used by constructor if available.
     *
     * @return void
     */
    public function testDefaultProfile()
    {
        $config = ['to' => 'foo@bar.com', 'from' => 'from@bar.com'];

        Configure::write('Mailer.default', $config);
        Mailer::setConfig(Configure::consume('Mailer'));

        $mailer = new TestMailer();
        $this->assertSame(['foo@bar.com' => 'foo@bar.com'], $mailer->getTo());
        $this->assertSame(['from@bar.com' => 'from@bar.com'], $mailer->getFrom());

        Configure::delete('Mailer');
        Mailer::drop('default');
    }

    /**
     * Test that using an invalid profile fails.
     *
     */
    public function testProfileInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown email configuration "derp".');
        $mailer = new TestMailer();
        $mailer->setProfile('derp');
    }

    /**
     * testConfigString method
     *
     * @return void
     */
    public function testUseConfigString()
    {
        $config = [
            'from' => ['some@example.com' => 'My website'],
            'to' => ['test@example.com' => 'Testname'],
            'subject' => 'Test mail subject',
            'transport' => 'debug',
            'theme' => 'TestTheme',
            'helpers' => ['Html', 'Form'],
        ];
        Mailer::setConfig('test', $config);
        $this->mailer->setProfile('test');

        $result = $this->mailer->getTo();
        $this->assertEquals($config['to'], $result);

        $result = $this->mailer->getFrom();
        $this->assertEquals($config['from'], $result);

        $result = $this->mailer->getSubject();
        $this->assertEquals($config['subject'], $result);

        $result = $this->mailer->viewBuilder()->getTheme();
        $this->assertEquals($config['theme'], $result);

        $result = $this->mailer->getTransport();
        $this->assertInstanceOf(DebugTransport::class, $result);

        $result = $this->mailer->viewBuilder()->getHelpers();
        $this->assertEquals($config['helpers'], $result);
    }

    /**
     * CakeEmailTest::testMockTransport()
     */
    public function testMockTransport()
    {
        TransportFactory::drop('default');

        $mock = $this->getMockBuilder(AbstractTransport::class)->getMock();
        $config = ['from' => 'tester@example.org', 'transport' => 'default'];

        Mailer::setConfig('default', $config);
        TransportFactory::setConfig('default', $mock);

        $em = new TestMailer('default');

        $this->assertSame($mock, $em->getTransport());
    }

    /**
     * @return void
     */
    public function testReset()
    {
        $mailer = new TestMailer();
        $email = $mailer->getEmailForAssertion();

        $mailer->set(['foo' => 'bar']);
        $this->assertNotEquals($email->getViewVars(), $mailer->reset()->getEmailForAssertion()->getViewVars());
    }

    /**
     * @return void
     */
    public function testGetName()
    {
        $result = (new TestMailer())->getName();
        $expected = 'Test';
        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
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

        $email = $this->getMockForEmail('setAttachments');
        $email->expects($this->once())
            ->method('setAttachments')
            ->with([
                ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
            ]);
        $result = (new TestMailer($email))->setAttachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
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
        $result = $mailer
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

        $mailer->send('test', ['foo', 'bar']);
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
     *
     * @return void
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
     * @return void
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

        $mailer->send('test', ['foo', 'bar']);
        $this->assertSame('cakephp', $mailer->viewBuilder()->getTemplate());
    }

    /**
     * @return void
     */
    public function testMissingActionThrowsException()
    {
        $this->expectException(\Cake\Mailer\Exception\MissingActionException::class);
        $this->expectExceptionMessage('Mail TestMailer::test() could not be found, or is not accessible.');
        (new TestMailer())->send('test');
    }
}
