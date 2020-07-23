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
use Cake\Log\Log;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Exception\MissingActionException;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\MissingTemplateException;
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
        Mailer::drop('test');
        Mailer::drop('default');
        Log::drop('email');
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
     */
    public function testTransportInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "Invalid" transport configuration does not exist');
        $this->mailer->setTransport('Invalid');
    }

    /**
     * Test that using classes with no send method fails.
     */
    public function testTransportInstanceInvalid()
    {
        $this->expectException(Exception::class);
        $this->mailer->setTransport(new \stdClass());
    }

    /**
     * Test that using unknown transports fails.
     */
    public function testTransportTypeInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value passed for the "$name" argument must be either a string, or an object, integer given.');
        $this->mailer->setTransport(123);
    }

    /**
     * testMessage function
     *
     * @return void
     */
    public function testMessage()
    {
        $message = $this->mailer->getMessage();
        $this->assertInstanceOf(Message::class, $message);

        $newMessage = new Message();
        $this->mailer->setMessage($newMessage);
        $this->assertSame($newMessage, $this->mailer->getMessage());
        $this->assertNotSame($message, $newMessage);
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

        $mailer = new Mailer('test');
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
     * testConstructWithConfigArray method
     *
     * @return void
     */
    public function testConstructWithConfigArray()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
        ];
        $this->mailer = new Mailer($configs);

        $result = $this->mailer->getTo();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->mailer->getFrom();
        $this->assertEquals($configs['from'], $result);

        $result = $this->mailer->getSubject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->mailer->getTransport();
        $this->assertInstanceOf(DebugTransport::class, $result);

        $result = $this->mailer->deliver('This is the message');

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testConfigArrayWithLayoutWithoutTemplate method
     *
     * @return void
     */
    public function testConfigArrayWithLayoutWithoutTemplate()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
            'layout' => 'custom',
        ];
        $this->mailer = new Mailer($configs);

        $template = $this->mailer->viewBuilder()->getTemplate();
        $layout = $this->mailer->viewBuilder()->getLayout();
        $this->assertNull($template);
        $this->assertEquals($configs['layout'], $layout);
    }

    /**
     * testConstructWithConfigString method
     *
     * @return void
     */
    public function testConstructWithConfigString()
    {
        $configs = [
            'from' => ['some@example.com' => 'My website'],
            'to' => 'test@example.com',
            'subject' => 'Test mail subject',
            'transport' => 'debug',
        ];
        Mailer::setConfig('test', $configs);

        $this->mailer = new Mailer('test');

        $result = $this->mailer->getTo();
        $this->assertEquals([$configs['to'] => $configs['to']], $result);

        $result = $this->mailer->getFrom();
        $this->assertEquals($configs['from'], $result);

        $result = $this->mailer->getSubject();
        $this->assertEquals($configs['subject'], $result);

        $result = $this->mailer->getTransport();
        $this->assertInstanceOf('Cake\Mailer\Transport\DebugTransport', $result);

        $result = $this->mailer->deliver('This is the message');

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
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

        $mailer = new Mailer();
        $this->assertSame(['foo@bar.com' => 'foo@bar.com'], $mailer->getTo());
        $this->assertSame(['from@bar.com' => 'from@bar.com'], $mailer->getFrom());

        Configure::delete('Mailer');
        Mailer::drop('default');
    }

    /**
     * Test that using an invalid profile fails.
     */
    public function testProfileInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown email configuration "derp".');
        $mailer = new Mailer();
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

        Mailer::drop('test');
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

        $em = new Mailer('default');

        $this->assertSame($mock, $em->getTransport());

        TransportFactory::drop('default');
    }

    /**
     * @return void
     */
    public function testProxies()
    {
        $result = (new Mailer())->setHeaders(['X-Something' => 'nice']);
        $this->assertInstanceOf(Mailer::class, $result);
        $header = $result->getMessage()->getHeaders();
        $this->assertSame('nice', $header['X-Something']);

        $result = (new Mailer())->setAttachments([
            ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain'],
        ]);
        $this->assertInstanceOf(Mailer::class, $result);
        $this->assertSame(
            ['basics.php' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']],
            $result->getMessage()->getAttachments()
        );
    }

    /**
     * Test that get/set methods can be proxied.
     *
     * @return void
     */
    public function testGetSetProxies()
    {
        $mailer = new Mailer();
        $result = $mailer
            ->setTo('test@example.com')
            ->setCc('cc@example.com');
        $this->assertSame($result, $mailer);

        $this->assertSame(['test@example.com' => 'test@example.com'], $result->getTo());
        $this->assertSame(['cc@example.com' => 'cc@example.com'], $result->getCc());
    }

    public function testSet()
    {
        $result = (new Mailer())->setViewVars('key', 'value');
        $this->assertInstanceOf(Mailer::class, $result);
        $this->assertSame(['key' => 'value'], $result->getRenderer()->viewBuilder()->getVars());
    }

    /**
     * testRenderWithLayoutAndAttachment method
     *
     * @return void
     */
    public function testRenderWithLayoutAndAttachment()
    {
        $this->mailer->setEmailFormat('html');
        $this->mailer->viewBuilder()->setTemplate('html', 'default');
        $this->mailer->setAttachments([CAKE . 'basics.php']);
        $this->mailer->render();
        $result = $this->mailer->getBody();
        $this->assertNotEmpty($result);

        $result = $this->mailer->getBoundary();
        $this->assertRegExp('/^[0-9a-f]{32}$/', $result);
    }

    public function testSend()
    {
        $mailer = $this->getMockBuilder(Mailer::class)
            ->onlyMethods(['deliver'])
            ->addMethods(['test'])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');
        $mailer->expects($this->any())
            ->method('deliver')
            ->will($this->returnValue([]));

        $mailer->send('test', ['foo', 'bar']);

        $this->assertNull($mailer->viewBuilder()->getTemplate());
    }

    /**
     * Calling send() with no parameters should not overwrite the view variables.
     *
     * @return void
     */
    public function testSendWithNoContentDoesNotOverwriteViewVar()
    {
        $this->mailer->reset();
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('you@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('text');
        $this->mailer->viewBuilder()
            ->setTemplate('default')
            ->setVars([
                'content' => 'A message to you',
            ]);

        $result = $this->mailer->send();
        $this->assertStringContainsString('A message to you', $result['message']);
    }

    /**
     * testSendWithContent method
     *
     * @return void
     */
    public function testSendWithContent()
    {
        $this->mailer->reset();
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);

        $result = $this->mailer->deliver("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");
        $expected = ['headers', 'message'];
        $this->assertEquals($expected, array_keys($result));
        $expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";

        $this->assertEquals($expected, $result['message']);
        $this->assertStringContainsString('Date: ', $result['headers']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $result = $this->mailer->deliver('Other body');
        $expected = "Other body\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * test send without a transport method
     *
     * @return void
     */
    public function testSendWithoutTransport()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Transport was not defined. You must set on using setTransport() or set `transport` option in your mailer profile.'
        );
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->send();
    }

    /**
     * Test send() with no template.
     *
     * @return void
     */
    public function testSendNoTemplateWithAttachments()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('text');
        $this->mailer->setAttachments([CAKE . 'basics.php']);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test send() with no template and data string attachment
     *
     * @return void
     */
    public function testSendNoTemplateWithDataStringAttachment()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('text');
        $data = file_get_contents(TEST_APP . 'webroot/img/cake.power.gif');
        $this->mailer->setAttachments(['cake.icon.gif' => [
                'data' => $data,
                'mimetype' => 'image/gif',
        ]]);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n" .
                "Content-Transfer-Encoding: 8bit\r\n" .
                "\r\n" .
                'Hello' .
                "\r\n" .
                "\r\n" .
                "\r\n" .
                "--$boundary\r\n" .
                "Content-Disposition: attachment; filename=\"cake.icon.gif\"\r\n" .
                "Content-Type: image/gif\r\n" .
                "Content-Transfer-Encoding: base64\r\n\r\n";
        $expected .= chunk_split(base64_encode($data), 76, "\r\n");
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test send() with no template as both
     *
     * @return void
     */
    public function testSendNoTemplateWithAttachmentsAsBoth()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('both');
        $this->mailer->setAttachments([CORE_PATH . 'VERSION.txt']);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Disposition: attachment; filename=\"VERSION.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * Test setting inline attachments and messages.
     *
     * @return void
     */
    public function testSendWithInlineAttachments()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('both');
        $this->mailer->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123',
            ],
        ]);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Type: multipart/alternative; boundary=\"alt-$boundary\"\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--alt-{$boundary}--\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <abc123>\r\n" .
            "\r\n";
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--rel-' . $boundary . '--', $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test setting inline attachments and HTML only messages.
     *
     * @return void
     */
    public function testSendWithInlineAttachmentsHtmlOnly()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('html');
        $this->mailer->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentId' => 'abc123',
            ],
        ]);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: multipart/related; boundary=\"rel-$boundary\"\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--rel-$boundary\r\n" .
            "Content-Disposition: inline; filename=\"cake.png\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <abc123>\r\n" .
            "\r\n";
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--rel-' . $boundary . '--', $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * Test disabling content-disposition.
     *
     * @return void
     */
    public function testSendWithNoContentDispositionAttachments()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setEmailFormat('text');
        $this->mailer->setAttachments([
            'cake.png' => [
                'file' => CORE_PATH . 'VERSION.txt',
                'contentDisposition' => false,
            ],
        ]);
        $result = $this->mailer->deliver('Hello');

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="' . $boundary . '"', $result['headers']);
        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            'Hello' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "\r\n";

        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('--' . $boundary . '--', $result['message']);
    }

    /**
     * testSendRender method
     *
     * @return void
     */
    public function testSendRender()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('default', 'default');
        $result = $this->mailer->send();

        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * test sending and rendering with no layout
     *
     * @return void
     */
    public function testSendRenderNoLayout()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setConfig(['empty']);
        $this->mailer->viewBuilder()
            ->setTemplate('default')
            ->setVar('content', 'message body.')
            ->disableAutoLayout();
        $result = $this->mailer->send();

        $this->assertStringContainsString('message body.', $result['message']);
        $this->assertStringNotContainsString('This email was sent using the CakePHP Framework', $result['message']);
    }

    /**
     * testSendRender both method
     *
     * @return void
     */
    public function testSendRenderBoth()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('default', 'default');
        $this->mailer->setEmailFormat('both');
        $result = $this->mailer->send();

        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $boundary = $this->mailer->boundary;
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary="' . $boundary . '"', $result['headers']);

        $expected = "--$boundary\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            'This email was sent using the CakePHP Framework, https://cakephp.org.' .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary\r\n" .
            "Content-Type: text/html; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n" .
            "\r\n" .
            '<!DOCTYPE html';
        $this->assertStringStartsWith($expected, $result['message']);

        $expected = "</html>\r\n" .
            "\r\n" .
            "\r\n" .
            "\r\n" .
            "--$boundary--\r\n";
        $this->assertStringEndsWith($expected, $result['message']);
    }

    /**
     * testSendRender method for ISO-2022-JP
     *
     * @return void
     */
    public function testSendRenderJapanese()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('default');
        $this->mailer->viewBuilder()->setLayout('japanese');
        $this->mailer->setCharset('ISO-2022-JP');
        $result = $this->mailer->send();

        $expected = mb_convert_encoding('CakePHP Framework を使って送信したメールです。 https://cakephp.org.', 'ISO-2022-JP');
        $this->assertStringContainsString($expected, $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * testSendRenderThemed method
     *
     * @return void
     */
    public function testSendRenderThemed()
    {
        $this->loadPlugins(['TestTheme']);
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTheme('TestTheme');
        $this->mailer->viewBuilder()->setTemplate('themed', 'default');
        $result = $this->mailer->send();

        $this->assertStringContainsString('In TestTheme', $result['message']);
        $this->assertStringContainsString('/test_theme/img/test.jpg', $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
        $this->assertStringContainsString('/test_theme/img/test.jpg', $result['message']);
        $this->clearPlugins();
    }

    /**
     * testSendRenderWithHTML method and assert line length is kept below the required limit
     *
     * @return void
     */
    public function testSendRenderWithHTML()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->setEmailFormat('html');
        $this->mailer->viewBuilder()->setTemplate('html', 'default');
        $result = $this->mailer->send();

        $this->assertTextContains('<h1>HTML Ipsum Presents</h1>', $result['message']);
        $this->assertLineLengths($result['message']);
    }

    /**
     * testSendRenderWithVars method
     *
     * @return void
     */
    public function testSendRenderWithVars()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('custom', 'default');
        $this->mailer->setViewVars(['value' => 12345]);
        $result = $this->mailer->send();

        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
    }

    /**
     * testSendRenderWithVars method for ISO-2022-JP
     *
     * @return void
     */
    public function testSendRenderWithVarsJapanese()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('japanese', 'default');
        $this->mailer->setViewVars(['value' => '日本語の差し込み123']);
        $this->mailer->setCharset('ISO-2022-JP');
        $result = $this->mailer->send();

        $expected = mb_convert_encoding('ここにあなたの設定した値が入ります: 日本語の差し込み123', 'ISO-2022-JP');
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * testSendRenderWithHelpers method
     *
     * @return void
     */
    public function testSendRenderWithHelpers()
    {
        $this->mailer->setTransport('debug');

        $timestamp = time();
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()
            ->setTemplate('custom_helper')
            ->setLayout('default')
            ->setHelpers(['Time'], false);
        $this->mailer->setViewVars(['time' => $timestamp]);

        $result = $this->mailer->send();
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($timestamp);
        $this->assertStringContainsString('Right now: ' . $dateTime->format($dateTime::ATOM), $result['message']);

        $result = $this->mailer->viewBuilder()->getHelpers();
        $this->assertEquals(['Time'], $result);
    }

    /**
     * testSendRenderWithImage method
     *
     * @return void
     */
    public function testSendRenderWithImage()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('image');
        $this->mailer->setEmailFormat('html');
        $server = env('SERVER_NAME') ? env('SERVER_NAME') : 'localhost';

        if (env('SERVER_PORT') && env('SERVER_PORT') !== 80) {
            $server .= ':' . env('SERVER_PORT');
        }

        $expected = '<img src="http://' . $server . '/img/image.gif" alt="cool image" width="100" height="100"';
        $result = $this->mailer->send();
        $this->assertStringContainsString($expected, $result['message']);
    }

    /**
     * testSendRenderPlugin method
     *
     * @return void
     */
    public function testSendRenderPlugin()
    {
        $this->loadPlugins(['TestPlugin', 'TestPluginTwo', 'TestTheme']);

        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);

        $this->mailer->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('default');
        $result = $this->mailer->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the CakePHP Framework', $result['message']);

        $this->mailer->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->mailer->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);

        $this->mailer->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('plug_default');
        $result = $this->mailer->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);

        $this->mailer->viewBuilder()
            ->setTemplate('TestPlugin.test_plugin_tpl')
            ->setLayout('TestPluginTwo.default');
        $result = $this->mailer->send();
        $this->assertStringContainsString('Into TestPlugin.', $result['message']);
        $this->assertStringContainsString('This email was sent using TestPluginTwo.', $result['message']);

        // test plugin template overridden by theme
        $this->mailer->viewBuilder()->setTheme('TestTheme');
        $result = $this->mailer->send();

        $this->assertStringContainsString('Into TestPlugin. (themed)', $result['message']);

        $this->mailer->setViewVars(['value' => 12345]);
        $this->mailer->viewBuilder()
            ->setTemplate('custom')
            ->setLayout('TestPlugin.plug_default');
        $result = $this->mailer->send();
        $this->assertStringContainsString('Here is your value: 12345', $result['message']);
        $this->assertStringContainsString('This email was sent using the TestPlugin.', $result['message']);
        $this->clearPlugins();
    }

    /**
     * Test that a MissingTemplateException is thrown
     *
     * @return void
     */
    public function testMissingTemplateException()
    {
        $this->expectException(MissingTemplateException::class);

        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->viewBuilder()->setTemplate('fooo');
        $this->mailer->send();
    }

    /**
     * testSendMultipleMIME method
     *
     * @return void
     */
    public function testSendMultipleMIME()
    {
        $this->mailer->setTransport('debug');

        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->viewBuilder()->setTemplate('custom', 'default');
        $this->mailer->setProfile([]);
        $this->mailer->setViewVars(['value' => 12345]);
        $this->mailer->setEmailFormat('both');
        $this->mailer->send();

        $message = $this->mailer->getBody();
        $boundary = $this->mailer->boundary;
        $this->assertNotEmpty($boundary);
        $this->assertContains('--' . $boundary, $message);
        $this->assertContains('--' . $boundary . '--', $message);

        $this->mailer->setAttachments(['fake.php' => __FILE__]);
        $this->mailer->send();

        $message = $this->mailer->getBody();
        $boundary = $this->mailer->boundary;
        $this->assertNotEmpty($boundary);
        $this->assertContains('--' . $boundary, $message);
        $this->assertContains('--' . $boundary . '--', $message);
        $this->assertContains('--alt-' . $boundary, $message);
        $this->assertContains('--alt-' . $boundary . '--', $message);
    }

    /**
     * testSendAttachment method
     *
     * @return void
     */
    public function testSendAttachment()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile([]);
        $this->mailer->setAttachments([CAKE . 'basics.php']);
        $this->mailer->setBodyText('body');
        $result = $this->mailer->send();
        $expected = "Content-Disposition: attachment; filename=\"basics.php\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertStringContainsString($expected, $result['message']);

        $this->mailer->setAttachments(['my.file.txt' => CAKE . 'basics.php']);
        $this->mailer->setBodyText('body');
        $result = $this->mailer->send();
        $expected = "Content-Disposition: attachment; filename=\"my.file.txt\"\r\n" .
            "Content-Type: text/x-php\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertStringContainsString($expected, $result['message']);

        $this->mailer->setAttachments(['file.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain']]);
        $this->mailer->setBodyText('body');
        $result = $this->mailer->send();
        $expected = "Content-Disposition: attachment; filename=\"file.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n";
        $this->assertStringContainsString($expected, $result['message']);

        $this->mailer->setAttachments(['file2.txt' => ['file' => CAKE . 'basics.php', 'mimetype' => 'text/plain', 'contentId' => 'a1b1c1']]);
        $this->mailer->setBodyText('body');
        $result = $this->mailer->send();
        $expected = "Content-Disposition: inline; filename=\"file2.txt\"\r\n" .
            "Content-Type: text/plain\r\n" .
            "Content-Transfer-Encoding: base64\r\n" .
            "Content-ID: <a1b1c1>\r\n";
        $this->assertStringContainsString($expected, $result['message']);
    }

    public function testSendWithUnsetTemplateDefaultsToActionName()
    {
        $mailer = $this->getMockBuilder(Mailer::class)
            ->onlyMethods(['deliver', 'restore'])
            ->addMethods(['test'])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');
        $mailer->expects($this->any())
            ->method('deliver')
            ->will($this->returnValue([]));

        $mailer->send('test', ['foo', 'bar']);
        $this->assertEquals('test', $mailer->viewBuilder()->getTemplate());
    }

    /**
     * testGetBody method
     *
     * @return void
     */
    public function testGetBody()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['empty']);
        $this->mailer->viewBuilder()->setTemplate('default', 'default');
        $this->mailer->setEmailFormat('both');
        $this->mailer->send();

        $expected = '<p>This email was sent using the <a href="https://cakephp.org">CakePHP Framework</a></p>';
        $this->assertStringContainsString($expected, $this->mailer->getBodyHtml());

        $expected = 'This email was sent using the CakePHP Framework, https://cakephp.org.';
        $this->assertStringContainsString($expected, $this->mailer->getBodyText());

        $message = $this->mailer->getBody();
        $this->assertContains('Content-Type: text/plain; charset=UTF-8', $message);
        $this->assertContains('Content-Type: text/html; charset=UTF-8', $message);

        // UTF-8 is 8bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '8bit'));

        $this->mailer->setCharset('ISO-2022-JP');
        $this->mailer->send();
        $message = $this->mailer->getBody();
        $this->assertContains('Content-Type: text/plain; charset=ISO-2022-JP', $message);
        $this->assertContains('Content-Type: text/html; charset=ISO-2022-JP', $message);

        // ISO-2022-JP is 7bit
        $this->assertTrue($this->_checkContentTransferEncoding($message, '7bit'));
    }

    /**
     * testZeroOnlyLinesNotBeingEmptied()
     *
     * @return void
     */
    public function testZeroOnlyLinesNotBeingEmptied()
    {
        $message = "Lorem\r\n0\r\n0\r\nipsum";

        $this->mailer->reset();
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->setSubject('Wordwrap Test');

        $result = $this->mailer->deliver($message);
        $expected = "{$message}\r\n\r\n";
        $this->assertEquals($expected, $result['message']);
    }

    /**
     * testReset method
     *
     * @return void
     */
    public function testReset()
    {
        $this->mailer->setTo('cake@cakephp.org');
        $this->mailer->viewBuilder()->setTheme('TestTheme');
        $this->mailer->setEmailPattern('/.+@.+\..+/i');
        $this->assertSame(['cake@cakephp.org' => 'cake@cakephp.org'], $this->mailer->getTo());

        $this->mailer->reset();
        $this->assertSame([], $this->mailer->getTo());
        $this->assertNull($this->mailer->viewBuilder()->getTheme());
        $this->assertSame(Message::EMAIL_PATTERN, $this->mailer->getEmailPattern());
    }

    /**
     * Test that mailers call reset() when send fails
     *
     * @return void
     */
    public function testSendFailsEmailIsReset()
    {
        $mailer = $this->getMockBuilder(Mailer::class)
            ->onlyMethods(['restore', 'deliver'])
            ->addMethods(['welcome'])
            ->getMock();

        $mailer->expects($this->once())
            ->method('deliver')
            ->will($this->throwException(new RuntimeException('kaboom')));
        // Mailer should be reset even if sending fails.
        $mailer->expects($this->once())
            ->method('restore');

        try {
            $mailer->send('welcome', ['foo', 'bar']);
            $this->fail('Exception should bubble up.');
        } catch (RuntimeException $e) {
            $this->assertTrue(true, 'Exception was raised');
        }
    }

    /**
     * testSendWithLog method
     *
     * @return void
     */
    public function testSendWithLog()
    {
        Log::setConfig('email', [
            'className' => 'Array',
        ]);

        $this->mailer->setTransport('debug');
        $this->mailer->setTo('me@cakephp.org');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['log' => 'debug']);

        $text = 'Logging This';
        $result = $this->mailer->deliver($text);
        $this->assertNotEmpty($result);

        $messages = Log::engine('email')->read();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString($text, $messages[0]);
        $this->assertStringContainsString('cake@cakephp.org', $messages[0]);
        $this->assertStringContainsString('me@cakephp.org', $messages[0]);
    }

    /**
     * testSendWithLogAndScope method
     *
     * @return void
     */
    public function testSendWithLogAndScope()
    {
        Log::setConfig('email', [
            'className' => 'Array',
            'scopes' => ['email'],
        ]);

        $this->mailer->setTransport('debug');
        $this->mailer->setTo('me@cakephp.org');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setSubject('My title');
        $this->mailer->setProfile(['log' => ['scope' => 'email']]);
        $text = 'Logging This';
        $this->mailer->deliver($text);

        $messages = Log::engine('email')->read();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString($text, $messages[0]);
        $this->assertStringContainsString('cake@cakephp.org', $messages[0]);
        $this->assertStringContainsString('me@cakephp.org', $messages[0]);
    }

    /**
     * test that initial email instance config is restored after email is sent.
     *
     * @return void
     */
    public function testDefaultProfileRestoration()
    {
        $mailer = $this->getMockBuilder(Mailer::class)
            ->onlyMethods(['deliver'])
            ->addMethods(['test'])
            ->setConstructorArgs([['template' => 'cakephp']])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');
        $mailer->expects($this->once())
            ->method('deliver')
            ->will($this->returnValue([]));

        $mailer->send('test', ['foo', 'bar']);
        $this->assertSame('cakephp', $mailer->viewBuilder()->getTemplate());
    }

    /**
     * @return void
     */
    public function testMissingActionThrowsException()
    {
        $this->expectException(MissingActionException::class);
        $this->expectExceptionMessage('Mail Cake\Mailer\Mailer::test() could not be found, or is not accessible.');
        (new Mailer())->send('test');
    }

    public function testDeliver()
    {
        $this->mailer->setTransport('debug');
        $this->mailer->setFrom('cake@cakephp.org');
        $this->mailer->setTo(['you@cakephp.org' => 'You']);
        $this->mailer->setSubject('My title');

        $result = $this->mailer->deliver("Here is my body, with multi lines.\nThis is the second line.\r\n\r\nAnd the last.");
        $expected = ['headers', 'message'];
        $this->assertEquals($expected, array_keys($result));
        $expected = "Here is my body, with multi lines.\r\nThis is the second line.\r\n\r\nAnd the last.\r\n\r\n";

        $this->assertEquals($expected, $result['message']);
        $this->assertStringContainsString('Date: ', $result['headers']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);

        $result = $this->mailer->deliver('Other body');
        $expected = "Other body\r\n\r\n";
        $this->assertSame($expected, $result['message']);
        $this->assertStringContainsString('Message-ID: ', $result['headers']);
        $this->assertStringContainsString('To: ', $result['headers']);
    }

    /**
     * @param string $message
     * @return void
     */
    protected function assertLineLengths($message)
    {
        $lines = explode("\r\n", $message);
        foreach ($lines as $line) {
            $this->assertTrue(
                strlen($line) <= Message::LINE_LENGTH_MUST,
                'Line length exceeds the max. limit of Message::LINE_LENGTH_MUST'
            );
        }
    }

    protected function _checkContentTransferEncoding($message, $charset)
    {
        $boundary = '--' . $this->mailer->getBoundary();
        $result['text'] = false;
        $result['html'] = false;
        $length = count($message);
        for ($i = 0; $i < $length; ++$i) {
            if ($message[$i] === $boundary) {
                $flag = false;
                $type = '';
                while (!preg_match('/^$/', $message[$i])) {
                    if (preg_match('/^Content-Type: text\/plain/', $message[$i])) {
                        $type = 'text';
                    }
                    if (preg_match('/^Content-Type: text\/html/', $message[$i])) {
                        $type = 'html';
                    }
                    if ($message[$i] === 'Content-Transfer-Encoding: ' . $charset) {
                        $flag = true;
                    }
                    ++$i;
                }
                $result[$type] = $flag;
            }
        }

        return $result['text'] && $result['html'];
    }
}
