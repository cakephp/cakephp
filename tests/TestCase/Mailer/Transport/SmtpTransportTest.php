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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer\Transport;

use Cake\Core\Exception\CakeException;
use Cake\Mailer\Message;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\Network\Exception\SocketException;
use Cake\Network\Socket;
use Cake\TestSuite\TestCase;
use ReflectionProperty;
use TestApp\Mailer\Transport\SmtpTestTransport;

/**
 * Test case
 */
class SmtpTransportTest extends TestCase
{
    /**
     * @var \TestApp\Mailer\Transport\SmtpTestTransport
     */
    protected $SmtpTransport;

    /**
     * @var \Cake\Network\Socket&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $socket;

    /**
     * @var array<string, string>
     */
    protected $credentials = [
        'username' => 'mark',
        'password' => 'story',
    ];

    /**
     * @var string
     */
    protected $credentialsEncoded;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->socket = $this->getMockBuilder(Socket::class)
            ->onlyMethods(['read', 'write', 'connect', 'disconnect', 'enableCrypto', 'isConnected'])
            ->getMock();

        $this->SmtpTransport = new SmtpTestTransport();
        $this->SmtpTransport->setSocket($this->socket);
        $this->SmtpTransport->setConfig(['client' => 'localhost']);

        $this->credentialsEncoded = base64_encode(chr(0) . 'mark' . chr(0) . 'story');
    }

    /**
     * testConnectEhlo method
     */
    public function testConnectEhlo(): void
    {
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls("220 Welcome message\r\n", "250 Accepted\r\n"));
        $this->socket->expects($this->once())->method('write')->with("EHLO localhost\r\n");
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectEhloTls method
     */
    public function testConnectEhloTls(): void
    {
        $this->SmtpTransport->setConfig(['tls' => true]);
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(4))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n",
                "220 Server ready\r\n",
                "250 Accepted\r\n"
            ));
        $this->socket->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["STARTTLS\r\n"],
                ["EHLO localhost\r\n"]
            );
        $this->socket->expects($this->once())->method('enableCrypto')
            ->with('tls')
            ->will($this->returnValue(true));

        $this->SmtpTransport->connect();
    }

    /**
     * testConnectEhloTlsOnNonTlsServer method
     */
    public function testConnectEhloTlsOnNonTlsServer(): void
    {
        $this->SmtpTransport->setConfig(['tls' => true]);
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(3))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n",
                "500 5.3.3 Unrecognized command\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["STARTTLS\r\n"]
            );

        $e = null;
        try {
            $this->SmtpTransport->connect();
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertSame('SMTP server did not accept the connection or trying to connect to non TLS SMTP server using TLS.', $e->getMessage());
        $this->assertInstanceOf(SocketException::class, $e->getPrevious());
        $this->assertStringContainsString('500 5.3.3 Unrecognized command', $e->getPrevious()->getMessage());
    }

    /**
     * testConnectEhloNoTlsOnRequiredTlsServer method
     */
    public function testConnectEhloNoTlsOnRequiredTlsServer(): void
    {
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('SMTP authentication method not allowed, check if SMTP server requires TLS.');
        $this->SmtpTransport->setConfig(['tls' => false] + $this->credentials);

        $this->socket->expects($this->exactly(4))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n",
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "504 5.7.4 Unrecognized authentication type\r\n"
            ));
        $this->socket->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"]
            );

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->SmtpTransport->connect();
    }

    public function testConnectEhloWithAuthPlain(): void
    {
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(3))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH PLAIN LOGIN\r\n",
                "235 OK\r\n",
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->connect();
        $this->assertEquals($this->SmtpTransport->getAuthType(), 'PLAIN');
    }

    public function testConnectEhloWithAuthLogin(): void
    {
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH LOGIN\r\n",
                "334 Login\r\n",
                "334 Pass\r\n",
                "235 OK\r\n"
            ));
        $this->socket->expects($this->exactly(4))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["AUTH LOGIN\r\n"],
                ["bWFyaw==\r\n"],
                ["c3Rvcnk=\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->connect();
        $this->assertEquals($this->SmtpTransport->getAuthType(), 'LOGIN');
    }

    /**
     * testConnectHelo method
     */
    public function testConnectHelo(): void
    {
        $this->socket->expects($this->exactly(3))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "200 Not Accepted\r\n",
                "250 Accepted\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["HELO localhost\r\n"]
            );

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectFail method
     */
    public function testConnectFail(): void
    {
        $this->socket->expects($this->exactly(3))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "200 Not Accepted\r\n",
                "200 Not Accepted\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["HELO localhost\r\n"]
            );
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $e = null;
        try {
            $this->SmtpTransport->connect();
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertSame('SMTP server did not accept the connection.', $e->getMessage());
        $this->assertInstanceOf(SocketException::class, $e->getPrevious());
        $this->assertStringContainsString('200 Not Accepted', $e->getPrevious()->getMessage());
    }

    /**
     * Test that when "authType" is specified that's that one used instead of the
     * 1st one supported by the server
     *
     * @return void
     */
    public function testAuthTypeSet(): void
    {
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH PLAIN LOGIN\r\n",
            ));
        $this->socket->expects($this->exactly(1))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
            );

        $this->SmtpTransport->setConfig(['authType' => SmtpTransport::AUTH_XOAUTH2]);
        $this->SmtpTransport->connect();
        $this->assertEquals($this->SmtpTransport->getAuthType(), SmtpTransport::AUTH_XOAUTH2);
    }

    public function testExceptionInvalidAuthType(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unsupported auth type. Available types are: PLAIN, LOGIN, XOAUTH2');

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH PLAIN LOGIN\r\n",
            ));
        $this->socket->expects($this->exactly(1))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
            );

        $this->SmtpTransport->setConfig(['authType' => 'invalid']);
        $this->SmtpTransport->connect();
    }

    public function testAuthTypeUnsupported(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unsupported auth type: CRAM-MD5');

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH CRAM-MD5\r\n",
            ));
        $this->socket->expects($this->exactly(1))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->connect();
    }

    public function testAuthTypeParsingIsSkippedIfNoCredentialsProvided(): void
    {
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 Accepted\r\n250 AUTH CRAM-MD5\r\n",
            ));
        $this->socket->expects($this->exactly(1))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
            );

        $this->SmtpTransport->connect();
        $this->assertNull($this->SmtpTransport->getAuthType());
    }

    public function testAuthPlain(): void
    {
        $this->socket->expects($this->once())->method('write')->with("AUTH PLAIN {$this->credentialsEncoded}\r\n");
        $this->socket->expects($this->once())->method('read')->will($this->returnValue("235 OK\r\n"));
        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuth method
     */
    public function testAuthLogin(): void
    {
        $this->socket->expects($this->exactly(4))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "334 Login\r\n",
                "334 Pass\r\n",
                "235 OK\r\n"
            ));
        $this->socket->expects($this->exactly(4))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"],
                ["bWFyaw==\r\n"],
                ["c3Rvcnk=\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuth method
     */
    public function testAuthXoauth2(): void
    {
        $authString = base64_encode(sprintf(
            "user=%s\1auth=Bearer %s\1\1",
            $this->credentials['username'],
            $this->credentials['password']
        ));

        $this->socket->expects($this->exactly(1))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "235 OK\r\n"
            ));
        $this->socket->expects($this->exactly(1))
            ->method('write')
            ->withConsecutive(
                ["AUTH XOAUTH2 {$authString}\r\n"],
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->setAuthType('XOAUTH2');
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthNotRecognized method
     */
    public function testAuthNotRecognized(): void
    {
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('AUTH command not recognized or not implemented, SMTP server may not require authentication.');

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "500 5.3.3 Unrecognized command\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthNotImplemented method
     */
    public function testAuthNotImplemented(): void
    {
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('AUTH command not recognized or not implemented, SMTP server may not require authentication.');

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "502 5.3.3 Command not implemented\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"]
            );
        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthBadSequence method
     */
    public function testAuthBadSequence(): void
    {
        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('SMTP Error: 503 5.5.1 Already authenticated');

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "503 5.5.1 Already authenticated\r\n"
            ));
        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"]
            );
        $this->SmtpTransport->setConfig($this->credentials);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthBadUsername method
     */
    public function testAuthBadUsername(): void
    {
        $this->socket->expects($this->exactly(3))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "334 Login\r\n",
                "535 5.7.8 Authentication failed\r\n"
            ));
        $this->socket->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"],
                ["bWFyaw==\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);

        $e = null;
        try {
            $this->SmtpTransport->auth();
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertSame('SMTP server did not accept the username.', $e->getMessage());
        $this->assertInstanceOf(SocketException::class, $e->getPrevious());
        $this->assertStringContainsString('535 5.7.8 Authentication failed', $e->getPrevious()->getMessage());
    }

    /**
     * testAuthBadPassword method
     */
    public function testAuthBadPassword(): void
    {
        $this->socket->expects($this->exactly(4))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "504 5.7.4 Unrecognized Authentication Type\r\n",
                "334 Login\r\n",
                "334 Pass\r\n",
                "535 5.7.8 Authentication failed\r\n"
            ));
        $this->socket->expects($this->exactly(4))
            ->method('write')
            ->withConsecutive(
                ["AUTH PLAIN {$this->credentialsEncoded}\r\n"],
                ["AUTH LOGIN\r\n"],
                ["bWFyaw==\r\n"],
                ["c3Rvcnk=\r\n"]
            );

        $this->SmtpTransport->setConfig($this->credentials);

        $e = null;
        try {
            $this->SmtpTransport->auth();
        } catch (SocketException $e) {
        }

        $this->assertNotNull($e);
        $this->assertSame('SMTP server did not accept the password.', $e->getMessage());
        $this->assertInstanceOf(SocketException::class, $e->getPrevious());
        $this->assertStringContainsString('535 5.7.8 Authentication failed', $e->getPrevious()->getMessage());
    }

    /**
     * testRcpt method
     */
    public function testRcpt(): void
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->setBcc('phpnut@cakephp.org');
        $message->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);

        $this->socket->expects($this->any())->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->exactly(5))
            ->method('write')
            ->withConsecutive(
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"],
                ["RCPT TO:<mark@cakephp.org>\r\n"],
                ["RCPT TO:<juan@cakephp.org>\r\n"],
                ["RCPT TO:<phpnut@cakephp.org>\r\n"]
            );

        $this->SmtpTransport->sendRcpt($message);
    }

    /**
     * testRcptWithReturnPath method
     */
    public function testRcptWithReturnPath(): void
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->setReturnPath('pleasereply@cakephp.org', 'CakePHP Return');

        $this->socket->expects($this->exactly(2))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["MAIL FROM:<pleasereply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"]
            );
        $this->SmtpTransport->sendRcpt($message);
    }

    /**
     * testSendData method
     */
    public function testSendData(): void
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setReturnPath('pleasereply@cakephp.org', 'CakePHP Return');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->setReplyTo(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $message->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $message->setBcc('phpnut@cakephp.org');
        $message->setMessageId('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
        $message->setSubject('Testing SMTP');
        $date = date(DATE_RFC2822);
        $message->setHeaders(['Date' => $date]);
        $message->setBody(['text' => "First Line\nSecond Line\n.Third Line"]);

        $data = "From: CakePHP Test <noreply@cakephp.org>\r\n";
        $data .= "Reply-To: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
        $data .= "Return-Path: CakePHP Return <pleasereply@cakephp.org>\r\n";
        $data .= "To: CakePHP <cake@cakephp.org>\r\n";
        $data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
        $data .= 'Date: ' . $date . "\r\n";
        $data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
        $data .= "Subject: Testing SMTP\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: 8bit\r\n";
        $data .= "\r\n";
        $data .= "First Line\r\n";
        $data .= "Second Line\r\n";
        $data .= "..Third Line\r\n\r\n"; // RFC5321 4.5.2.Transparency
        $data .= "\r\n";
        $data .= "\r\n\r\n.\r\n";

        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "354 OK\r\n",
                "250 OK\r\n"
            ));

        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["DATA\r\n"],
                [$data]
            );

        $this->SmtpTransport->sendData($message);
    }

    /**
     * testQuit method
     */
    public function testQuit(): void
    {
        $this->socket->expects($this->once())->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->once())->method('isConnected')->willReturn(true);

        $this->SmtpTransport->disconnect();
    }

    /**
     * Tests using empty client name
     */
    public function testEmptyClientName(): void
    {
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->any())
           ->method('read')
           ->will($this->onConsecutiveCalls("220 Welcome message\r\n", "250 Accepted\r\n"));

        $this->SmtpTransport->setConfig(['client' => '']);

        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('Cannot use an empty client name');
        $this->SmtpTransport->connect();
    }

    /**
     * testGetLastResponse method
     */
    public function testGetLastResponse(): void
    {
        $this->assertEmpty($this->SmtpTransport->getLastResponse());

        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->any())
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250-PIPELINING\r\n",
                "250-SIZE 102400000\r\n",
                "250-VRFY\r\n",
                "250-ETRN\r\n",
                "250-STARTTLS\r\n",
                "250-AUTH PLAIN LOGIN\r\n",
                "250-AUTH=PLAIN LOGIN\r\n",
                "250-ENHANCEDSTATUSCODES\r\n",
                "250-8BITMIME\r\n",
                "250 DSN\r\n"
            ));
        $this->socket->expects($this->once())->method('write')->with("EHLO localhost\r\n");
        $this->SmtpTransport->connect();

        $expected = [
            ['code' => '250', 'message' => 'PIPELINING'],
            ['code' => '250', 'message' => 'SIZE 102400000'],
            ['code' => '250', 'message' => 'VRFY'],
            ['code' => '250', 'message' => 'ETRN'],
            ['code' => '250', 'message' => 'STARTTLS'],
            ['code' => '250', 'message' => 'AUTH PLAIN LOGIN'],
            ['code' => '250', 'message' => 'AUTH=PLAIN LOGIN'],
            ['code' => '250', 'message' => 'ENHANCEDSTATUSCODES'],
            ['code' => '250', 'message' => '8BITMIME'],
            ['code' => '250', 'message' => 'DSN'],
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getLastResponse() with multiple operations
     */
    public function testGetLastResponseMultipleOperations(): void
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');

        $this->socket->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"]
            );
        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->sendRcpt($message);

        $expected = [
            ['code' => '250', 'message' => 'OK'],
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * testBufferResponseLines method
     */
    public function testBufferResponseLines(): void
    {
        $responseLines = [
            '123',
            "456\tFOO",
            'FOOBAR',
            '250-PIPELINING',
            '250-ENHANCEDSTATUSCODES',
            '250-8BITMIME',
            '250 DSN',
        ];
        $this->SmtpTransport->bufferResponseLines($responseLines);

        $expected = [
            ['code' => '123', 'message' => null],
            ['code' => '250', 'message' => 'PIPELINING'],
            ['code' => '250', 'message' => 'ENHANCEDSTATUSCODES'],
            ['code' => '250', 'message' => '8BITMIME'],
            ['code' => '250', 'message' => 'DSN'],
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * testExplicitConnectAlreadyConnected method
     */
    public function testExplicitConnectAlreadyConnected(): void
    {
        $this->socket->expects($this->never())->method('connect');
        $this->socket->expects($this->once())->method('isConnected')->willReturn(true);

        $this->SmtpTransport->connect();
    }

    /**
     * testConnected method
     */
    public function testConnected(): void
    {
        $this->socket->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(
                true,
                false
            ));

        $this->assertTrue($this->SmtpTransport->connected());
        $this->assertFalse($this->SmtpTransport->connected());
    }

    /**
     * testAutoDisconnect method
     */
    public function testAutoDisconnect(): void
    {
        $this->socket->expects($this->once())->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->once())->method('disconnect');
        $this->socket->expects($this->once())->method('isConnected')->willReturn(true);
        unset($this->SmtpTransport);
    }

    /**
     * testExplicitDisconnect method
     */
    public function testExplicitDisconnect(): void
    {
        $this->socket->expects($this->once())->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->once())->method('disconnect');
        $this->socket->expects($this->once())->method('isConnected')->willReturn(true);
        $this->SmtpTransport->disconnect();
    }

    /**
     * testExplicitDisconnectNotConnected method
     */
    public function testExplicitDisconnectNotConnected(): void
    {
        $callback = function ($arg): void {
            $this->assertNotEquals("QUIT\r\n", $arg);
        };
        $this->socket->expects($this->any())->method('write')->will($this->returnCallback($callback));
        $this->socket->expects($this->never())->method('disconnect');
        $this->SmtpTransport->disconnect();
    }

    /**
     * testKeepAlive method
     */
    public function testKeepAlive(): void
    {
        $this->SmtpTransport->setConfig(['keepAlive' => true]);

        /** @var \Cake\Mailer\Message $message */
        $message = $this->getMockBuilder(Message::class)
            ->onlyMethods(['getBody'])
            ->getMock();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->expects($this->exactly(2))->method('getBody')->will($this->returnValue(['First Line']));

        $callback = function ($arg) {
            $this->assertNotEquals("QUIT\r\n", $arg);

            return 1;
        };
        $this->socket->expects($this->any())->method('write')->will($this->returnCallback($callback));
        $this->socket->expects($this->never())->method('disconnect');

        $this->socket->expects($this->exactly(11))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "354 OK\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                // Second email
                "250 OK\r\n",
                "250 OK\r\n",
                "354 OK\r\n",
                "250 OK\r\n"
            ));
        $this->socket->expects($this->exactly(10))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"],
                ["DATA\r\n"],
                [$this->stringContains('First Line')],
                ["RSET\r\n"],
                // Second email
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"],
                ["DATA\r\n"],
                [$this->stringContains('First Line')]
            );

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->SmtpTransport->send($message);
        $this->socket->expects($this->once())->method('isConnected')->willReturn(true);
        $this->SmtpTransport->send($message);
    }

    /**
     * testSendDefaults method
     */
    public function testSendDefaults(): void
    {
        /** @var \Cake\Mailer\Message $message */
        $message = $this->getMockBuilder(Message::class)
            ->onlyMethods(['getBody'])
            ->getMock();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->expects($this->once())->method('getBody')->will($this->returnValue(['First Line']));

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->atLeast(6))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "354 OK\r\n",
                "250 OK\r\n"
            ));

        $this->socket->expects($this->atLeast(6))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"],
                ["DATA\r\n"],
                [$this->stringContains('First Line')],
                ["QUIT\r\n"]
            );

        $this->socket->expects($this->once())->method('disconnect');

        $this->SmtpTransport->send($message);
    }

    /**
     * testSendDefaults method
     */
    public function testSendMessageTooBigOnWindows(): void
    {
        /** @var \Cake\Mailer\Message $message */
        $message = $this->getMockBuilder(Message::class)
            ->onlyMethods(['getBody'])
            ->getMock();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->expects($this->once())->method('getBody')->will($this->returnValue(['First Line']));

        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->atLeast(6))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "250 OK\r\n",
                "354 OK\r\n",
                'Message size too large'
            ));

        $this->socket->expects($this->exactly(5))
            ->method('write')
            ->withConsecutive(
                ["EHLO localhost\r\n"],
                ["MAIL FROM:<noreply@cakephp.org>\r\n"],
                ["RCPT TO:<cake@cakephp.org>\r\n"],
                ["DATA\r\n"],
                [$this->stringContains('First Line')]
            );

        $this->expectException(SocketException::class);
        $this->expectExceptionMessage('Message size too large');

        $this->SmtpTransport->send($message);
    }

    /**
     * Ensure that unserialized transports have no connection.
     */
    public function testSerializeCleanupSocket(): void
    {
        $this->socket->expects($this->once())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->exactly(2))
            ->method('read')
            ->will($this->onConsecutiveCalls(
                "220 Welcome message\r\n",
                "250 OK\r\n"
            ));
        $this->socket->expects($this->once())
            ->method('write')
            ->with("EHLO localhost\r\n");

        $smtpTransport = new SmtpTestTransport();
        $smtpTransport->setSocket($this->socket);
        $smtpTransport->connect();

        $result = unserialize(serialize($smtpTransport));

        $reflect = new ReflectionProperty($result, '_socket');
        $reflect->setAccessible(true);
        $this->assertNull($reflect->getValue($result));
        $this->assertFalse($result->connected());
    }
}
