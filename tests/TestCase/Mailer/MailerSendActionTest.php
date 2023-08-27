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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Mailer\Mailer;
use Cake\TestSuite\TestCase;

class MailerSendActionTest extends TestCase
{
    public function testSendAction(): void
    {
        $mailer = $this->getMockBuilder(SendActionMailer::class)
            ->onlyMethods(['deliver', 'test'])
            ->getMock();
        $mailer->expects($this->once())
            ->method('test')
            ->with('foo', 'bar');
        $mailer->expects($this->any())
            ->method('deliver')
            ->willReturn([]);

        $mailer->send('test', ['foo', 'bar']);

        $this->assertNull($mailer->viewBuilder()->getTemplate());
    }
}

// phpcs:disable
class SendActionMailer extends Mailer
{
    public function test($to, $subject)
    {
    }
}
// phpcs:enable
