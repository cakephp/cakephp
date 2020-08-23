<?php
declare(strict_types=1);

/**
 * DebugTransportTest file
 *
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

use Cake\Mailer\Message;
use Cake\Mailer\Transport\DebugTransport;
use Cake\TestSuite\TestCase;

/**
 * Test case
 */
class DebugTransportTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->DebugTransport = new DebugTransport();
    }

    /**
     * testSend method
     *
     * @return void
     */
    public function testSend()
    {
        $message = new Message();
        $message->setFrom('noreply@cakephp.org', 'CakePHP Test');
        $message->setTo('cake@cakephp.org', 'CakePHP');
        $message->setCc(['mark@cakephp.org' => 'Mark Story', 'juan@cakephp.org' => 'Juan Basso']);
        $message->setBcc('phpnut@cakephp.org');
        $message->setMessageId('<4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>');
        $message->setSubject('Testing Message');
        $date = date(DATE_RFC2822);
        $message->setHeaders(['Date' => $date, 'o:tag' => ['foo', 'bar']]);
        $message->setBody(['text' => "First Line\nSecond Line\n.Third Line\n"]);

        $headers = "From: CakePHP Test <noreply@cakephp.org>\r\n";
        $headers .= "To: CakePHP <cake@cakephp.org>\r\n";
        $headers .= "Cc: Mark Story <mark@cakephp.org>, Juan Basso <juan@cakephp.org>\r\n";
        $headers .= 'Date: ' . $date . "\r\n";
        $headers .= 'o:tag: foo' . "\r\n";
        $headers .= 'o:tag: bar' . "\r\n";
        $headers .= "Message-ID: <4d9946cf-0a44-4907-88fe-1d0ccbdd56cb@localhost>\r\n";
        $headers .= "Subject: Testing Message\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= 'Content-Transfer-Encoding: 8bit';

        $data = "First Line\r\n";
        $data .= "Second Line\r\n";
        $data .= ".Third Line\r\n\r\n"; // Not use 'RFC5321 4.5.2.Transparency' in DebugTransport.

        $result = $this->DebugTransport->send($message);

        $this->assertSame($headers, $result['headers']);
        $this->assertSame($data, $result['message']);
    }
}
