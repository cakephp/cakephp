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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Mailer\Email;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;

class TestEmailTransportTest extends TestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Email::drop('default');
        Email::drop('alternate');
        TransportFactory::drop('transport_default');
        TransportFactory::drop('transport_alternate');

        TransportFactory::setConfig('transport_default', [
            'className' => DebugTransport::class,
        ]);
        TransportFactory::setConfig('transport_alternate', [
            'className' => DebugTransport::class,
        ]);

        Email::setConfig('default', [
            'transport' => 'transport_default',
            'from' => 'default@example.com',
        ]);
        Email::setConfig('alternate', [
            'transport' => 'transport_alternate',
            'from' => 'alternate@example.com',
        ]);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Email::drop('default');
        Email::drop('alternate');
        TransportFactory::drop('transport_default');
        TransportFactory::drop('transport_alternate');
    }

    /**
     * tests replaceAllTransports
     *
     * @return void
     */
    public function testReplaceAllTransports()
    {
        TestEmailTransport::replaceAllTransports();

        $config = TransportFactory::getConfig('transport_default');
        $this->assertSame(TestEmailTransport::class, $config['className']);

        $config = TransportFactory::getConfig('transport_alternate');
        $this->assertSame(TestEmailTransport::class, $config['className']);
    }

    /**
     * tests sending an email through the transport, getting it, and clearing all emails
     *
     * @return void
     */
    public function testSendGetAndClear()
    {
        TestEmailTransport::replaceAllTransports();

        (new Email())
            ->setTo('test@example.com')
            ->send('test');
        $this->assertCount(1, TestEmailTransport::getMessages());

        TestEmailTransport::clearMessages();
        $this->assertCount(0, TestEmailTransport::getMessages());
    }
}
