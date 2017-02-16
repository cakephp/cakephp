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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer\Transport;

use Cake\Mailer\Email;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\Network\Socket;
use Cake\TestSuite\TestCase;

/**
 * Help to test SmtpTransport
 */
class SmtpTestTransport extends SmtpTransport
{

    /**
     * Helper to change the socket
     *
     * @param Socket $socket
     * @return void
     */
    public function setSocket(Socket $socket)
    {
        $this->_socket = $socket;
    }

    /**
     * Disabled the socket change
     *
     * @return void
     */
    protected function _generateSocket()
    {
    }

    /**
     * Magic function to call protected methods
     *
     * @param string $method
     * @param string $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = '_' . $method;

        return call_user_func_array([$this, $method], $args);
    }
}

/**
 * Test case
 */
class SmtpTransportTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->socket = $this->getMockBuilder('Cake\Network\Socket')
            ->setMethods(['read', 'write', 'connect', 'disconnect', 'enableCrypto'])
            ->getMock();

        $this->SmtpTransport = new SmtpTestTransport();
        $this->SmtpTransport->setSocket($this->socket);
        $this->SmtpTransport->config(['client' => 'localhost']);
    }

    /**
     * testConnectEhlo method
     *
     * @return void
     */
    public function testConnectEhlo()
    {
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->any())
           ->method('read')
           ->will($this->onConsecutiveCalls(
               "220 Welcome message\r\n",
               "250 Accepted\r\n"
           ));
        $this->socket->expects($this->once())->method('write')->with("EHLO localhost\r\n");
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectEhloTls method
     *
     * @return void
     */
    public function testConnectEhloTls()
    {
        $this->SmtpTransport->config(['tls' => true]);
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 Accepted\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("STARTTLS\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("220 Server ready\r\n"));
        $this->socket->expects($this->at(6))->method('enableCrypto')->with('tls')->will($this->returnValue(true));
        $this->socket->expects($this->at(7))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(8))->method('read')->will($this->returnValue("250 Accepted\r\n"));
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectEhloTlsOnNonTlsServer method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP server did not accept the connection or trying to connect to non TLS SMTP server using TLS.
     * @return void
     */
    public function testConnectEhloTlsOnNonTlsServer()
    {
        $this->SmtpTransport->config(['tls' => true]);
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 Accepted\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("STARTTLS\r\n");
        $this->socket->expects($this->at(5))->method('read')
            ->will($this->returnValue("500 5.3.3 Unrecognized command\r\n"));
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectEhloNoTlsOnRequiredTlsServer method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP authentication method not allowed, check if SMTP server requires TLS.
     * @return void
     */
    public function testConnectEhloNoTlsOnRequiredTlsServer()
    {
        $this->SmtpTransport->config(['tls' => false, 'username' => 'user', 'password' => 'pass']);
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 Accepted\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(5))->method('read')
            ->will($this->returnValue("504 5.7.4 Unrecognized authentication type\r\n"));
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectHelo method
     *
     * @return void
     */
    public function testConnectHelo()
    {
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("HELO localhost\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 Accepted\r\n"));
        $this->SmtpTransport->connect();
    }

    /**
     * testConnectFail method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP server did not accept the connection.
     * @return void
     */
    public function testConnectFail()
    {
        $this->socket->expects($this->any())->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("HELO localhost\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("200 Not Accepted\r\n"));
        $this->SmtpTransport->connect();
    }

    /**
     * testAuth method
     *
     * @return void
     */
    public function testAuth()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("334 Login\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("bWFyaw==\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("334 Pass\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("c3Rvcnk=\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("235 OK\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthNotRecognized method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage AUTH command not recognized or not implemented, SMTP server may not require authentication.
     * @return void
     */
    public function testAuthNotRecognized()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))->method('read')
            ->will($this->returnValue("500 5.3.3 Unrecognized command\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthNotImplemented method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage AUTH command not recognized or not implemented, SMTP server may not require authentication.
     * @return void
     */
    public function testAuthNotImplemented()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))->method('read')
            ->will($this->returnValue("502 5.3.3 Command not implemented\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthBadSequence method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP Error: 503 5.5.1 Already authenticated
     * @return void
     */
    public function testAuthBadSequence()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))
            ->method('read')->will($this->returnValue("503 5.5.1 Already authenticated\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthBadUsername method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP server did not accept the username.
     * @return void
     */
    public function testAuthBadUsername()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("334 Login\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("bWFyaw==\r\n");
        $this->socket->expects($this->at(3))->method('read')
            ->will($this->returnValue("535 5.7.8 Authentication failed\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testAuthBadPassword method
     *
     * @expectedException \Cake\Network\Exception\SocketException
     * @expectedExceptionMessage SMTP server did not accept the password.
     * @return void
     */
    public function testAuthBadPassword()
    {
        $this->socket->expects($this->at(0))->method('write')->with("AUTH LOGIN\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("334 Login\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("bWFyaw==\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("334 Pass\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("c3Rvcnk=\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("535 5.7.8 Authentication failed\r\n"));
        $this->SmtpTransport->config(['username' => 'mark', 'password' => 'story']);
        $this->SmtpTransport->auth();
    }

    /**
     * testRcpt method
     *
     * @return void
     */
    public function testRcpt()
    {
        $email = new Email();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->bcc('phpnut@cakephp.org');
        $email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);

        $this->socket->expects($this->at(0))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(4))->method('write')->with("RCPT TO:<mark@cakephp.org>\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(6))->method('write')->with("RCPT TO:<juan@cakephp.org>\r\n");
        $this->socket->expects($this->at(7))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(8))->method('write')->with("RCPT TO:<phpnut@cakephp.org>\r\n");
        $this->socket->expects($this->at(9))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->sendRcpt($email);
    }

    /**
     * testRcptWithReturnPath method
     *
     * @return void
     */
    public function testRcptWithReturnPath()
    {
        $email = new Email();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->returnPath('pleasereply@cakephp.org', 'CakePHP Return');

        $this->socket->expects($this->at(0))->method('write')->with("MAIL FROM:<pleasereply@cakephp.org>\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->sendRcpt($email);
    }

    /**
     * testSendData method
     *
     * @return void
     */
    public function testSendData()
    {
        $email = $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods(['message'])
            ->getMock();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->returnPath('pleasereply@cakephp.org', 'CakePHP Return');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->cc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $email->bcc('phpnut@cakephp.org');
        $email->messageID('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
        $email->subject('Testing SMTP');
        $date = date(DATE_RFC2822);
        $email->setHeaders(['Date' => $date]);
        $email->expects($this->once())
            ->method('message')
            ->will($this->returnValue(['First Line', 'Second Line', '.Third Line', '']));

        $data = "From: CakePHP Test <noreply@cakephp.org>\r\n";
        $data .= "Return-Path: CakePHP Return <pleasereply@cakephp.org>\r\n";
        $data .= "To: CakePHP <cake@cakephp.org>\r\n";
        $data .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
        $data .= "Date: " . $date . "\r\n";
        $data .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
        $data .= "Subject: Testing SMTP\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: 8bit\r\n";
        $data .= "\r\n";
        $data .= "First Line\r\n";
        $data .= "Second Line\r\n";
        $data .= "..Third Line\r\n"; // RFC5321 4.5.2.Transparency
        $data .= "\r\n";
        $data .= "\r\n\r\n.\r\n";

        $this->socket->expects($this->at(0))->method('write')->with("DATA\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("354 OK\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with($data);
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->sendData($email);
    }

    /**
     * testQuit method
     *
     * @return void
     */
    public function testQuit()
    {
        $this->socket->expects($this->at(0))->method('write')->with("QUIT\r\n");
        $this->socket->connected = true;
        $this->SmtpTransport->disconnect();
    }

    /**
     * testEmptyConfigArray method
     *
     * @return void
     */
    public function testEmptyConfigArray()
    {
        $this->SmtpTransport->config([
            'client' => 'myhost.com',
            'port' => 666
        ]);
        $expected = $this->SmtpTransport->config();

        $this->assertEquals(666, $expected['port']);

        $this->SmtpTransport->config([]);
        $result = $this->SmtpTransport->config();
        $this->assertEquals($expected, $result);
    }

    /**
     * testGetLastResponse method
     *
     * @return void
     */
    public function testGetLastResponse()
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
            ['code' => '250', 'message' => 'DSN']
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getLastResponse() with multiple operations
     *
     * @return void
     */
    public function testGetLastResponseMultipleOperations()
    {
        $email = new Email();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->to('cake@cakephp.org', 'CakePHP');

        $this->socket->expects($this->at(0))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->sendRcpt($email);

        $expected = [
            ['code' => '250', 'message' => 'OK'],
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * testBufferResponseLines method
     *
     * @return void
     */
    public function testBufferResponseLines()
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
            ['code' => '250', 'message' => 'DSN']
        ];
        $result = $this->SmtpTransport->getLastResponse();
        $this->assertEquals($expected, $result);
    }

    /**
     * testExplicitConnectAlreadyConnected method
     *
     * @return void
     */
    public function testExplicitConnectAlreadyConnected()
    {
        $this->socket->expects($this->never())->method('connect');
        $this->socket->connected = true;
        $this->SmtpTransport->connect();
    }

    /**
     * testConnected method
     *
     * @return void
     */
    public function testConnected()
    {
        $this->socket->connected = true;
        $this->assertTrue($this->SmtpTransport->connected());

        $this->socket->connected = false;
        $this->assertFalse($this->SmtpTransport->connected());
    }

    /**
     * testAutoDisconnect method
     *
     * @return void
     */
    public function testAutoDisconnect()
    {
        $this->socket->expects($this->at(0))->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->at(1))->method('disconnect');
        $this->socket->connected = true;
        unset($this->SmtpTransport);
    }

    /**
     * testExplicitDisconnect method
     *
     * @return void
     */
    public function testExplicitDisconnect()
    {
        $this->socket->expects($this->at(0))->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->at(1))->method('disconnect');
        $this->socket->connected = true;
        $this->SmtpTransport->disconnect();
    }

    /**
     * testExplicitDisconnectNotConnected method
     *
     * @return void
     */
    public function testExplicitDisconnectNotConnected()
    {
        $callback = function ($arg) {
            $this->assertNotEquals("QUIT\r\n", $arg);
        };
        $this->socket->expects($this->any())->method('write')->will($this->returnCallback($callback));
        $this->socket->expects($this->never())->method('disconnect');
        $this->SmtpTransport->disconnect();
    }

    /**
     * testKeepAlive method
     *
     * @return void
     */
    public function testKeepAlive()
    {
        $this->SmtpTransport->config(['keepAlive' => true]);

        $email = $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods(['message'])
            ->getMock();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->expects($this->exactly(2))->method('message')->will($this->returnValue(['First Line']));

        $callback = function ($arg) {
            $this->assertNotEquals("QUIT\r\n", $arg);
        };
        $this->socket->expects($this->any())->method('write')->will($this->returnCallback($callback));
        $this->socket->expects($this->never())->method('disconnect');

        $this->socket->expects($this->at(0))->method('connect')->will($this->returnValue(true));
        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(4))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(6))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(7))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(8))->method('write')->with("DATA\r\n");
        $this->socket->expects($this->at(9))->method('read')->will($this->returnValue("354 OK\r\n"));
        $this->socket->expects($this->at(10))->method('write')->with($this->stringContains('First Line'));
        $this->socket->expects($this->at(11))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(12))->method('write')->with("RSET\r\n");
        $this->socket->expects($this->at(13))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(14))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
        $this->socket->expects($this->at(15))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(16))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(17))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(18))->method('write')->with("DATA\r\n");
        $this->socket->expects($this->at(19))->method('read')->will($this->returnValue("354 OK\r\n"));
        $this->socket->expects($this->at(20))->method('write')->with($this->stringContains('First Line'));
        $this->socket->expects($this->at(21))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->SmtpTransport->send($email);
        $this->socket->connected = true;
        $this->SmtpTransport->send($email);
    }

    /**
     * testSendDefaults method
     *
     * @return void
     */
    public function testSendDefaults()
    {
        $email = $this->getMockBuilder('Cake\Mailer\Email')
            ->setMethods(['message'])
            ->getMock();
        $email->from('noreply@cakephp.org', 'CakePHP Test');
        $email->to('cake@cakephp.org', 'CakePHP');
        $email->expects($this->once())->method('message')->will($this->returnValue(['First Line']));

        $this->socket->expects($this->at(0))->method('connect')->will($this->returnValue(true));

        $this->socket->expects($this->at(1))->method('read')->will($this->returnValue("220 Welcome message\r\n"));
        $this->socket->expects($this->at(2))->method('write')->with("EHLO localhost\r\n");
        $this->socket->expects($this->at(3))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(4))->method('write')->with("MAIL FROM:<noreply@cakephp.org>\r\n");
        $this->socket->expects($this->at(5))->method('read')->will($this->returnValue("250 OK\r\n"));
        $this->socket->expects($this->at(6))->method('write')->with("RCPT TO:<cake@cakephp.org>\r\n");
        $this->socket->expects($this->at(7))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(8))->method('write')->with("DATA\r\n");
        $this->socket->expects($this->at(9))->method('read')->will($this->returnValue("354 OK\r\n"));
        $this->socket->expects($this->at(10))->method('write')->with($this->stringContains('First Line'));
        $this->socket->expects($this->at(11))->method('read')->will($this->returnValue("250 OK\r\n"));

        $this->socket->expects($this->at(12))->method('write')->with("QUIT\r\n");
        $this->socket->expects($this->at(13))->method('disconnect');

        $this->SmtpTransport->send($email);
    }
}
