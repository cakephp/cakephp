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

use Cake\Mailer\Mailer;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\TestEmailTransport;

class TestEmailTransportTest extends TestCase
{
    /**
     * setUp
     */
    public function setUp(): void
    {
        parent::setUp();

        Mailer::drop('default');
        Mailer::drop('alternate');
        TransportFactory::drop('transport_default');
        TransportFactory::drop('transport_alternate');

        TransportFactory::setConfig('transport_default', [
            'className' => DebugTransport::class,
        ]);
        TransportFactory::setConfig('transport_alternate', [
            'className' => DebugTransport::class,
        ]);

        Mailer::setConfig('default', [
            'transport' => 'transport_default',
            'from' => 'default@example.com',
        ]);
        Mailer::setConfig('alternate', [
            'transport' => 'transport_alternate',
            'from' => 'alternate@example.com',
        ]);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Mailer::drop('default');
        Mailer::drop('alternate');
        TransportFactory::drop('transport_default');
        TransportFactory::drop('transport_alternate');
    }

    /**
     * tests replaceAllTransports
     */
    public function testReplaceAllTransports(): void
    {
        TestEmailTransport::replaceAllTransports();

        $config = TransportFactory::getConfig('transport_default');
        $this->assertSame(TestEmailTransport::class, $config['className']);

        $config = TransportFactory::getConfig('transport_alternate');
        $this->assertSame(TestEmailTransport::class, $config['className']);
    }

    /**
     * tests sending an email through the transport, getting it, and clearing all emails
     */
    public function testSendGetAndClear(): void
    {
        TestEmailTransport::replaceAllTransports();

        (new Mailer())
            ->setTo('test@example.com')
            ->deliver('test');
        $this->assertCount(1, TestEmailTransport::getMessages());

        TestEmailTransport::clearMessages();
        $this->assertCount(0, TestEmailTransport::getMessages());
    }
}
