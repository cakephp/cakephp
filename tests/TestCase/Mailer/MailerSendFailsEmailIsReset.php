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
use RuntimeException;

class MailerSendFailsEmailIsReset extends TestCase
{
    public function testSendAction(): void
    {
        $mailer = $this->getMockBuilder(SendFailsEmailIsResetMailer::class)
            ->onlyMethods(['restore', 'deliver', 'welcome'])
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
}

// phpcs:disable
class SendFailsEmailIsResetMailer extends Mailer
{
    public function welcome()
    {
    }
}
// phpcs:enable
