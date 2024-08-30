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
        $mailer = new class extends Mailer {
            public bool $restoreIsCalled = false;

            public function welcome()
            {
            }

            public function deliver(string $content = ''): array
            {
                throw new RuntimeException('kaboom');
            }

            protected function restore()
            {
                $this->restoreIsCalled = true;

                return $this;
            }
        };

        try {
            $mailer->send('welcome', ['foo', 'bar']);
            $this->fail('Exception should bubble up.');
        } catch (RuntimeException) {
            $this->assertTrue($mailer->restoreIsCalled, 'Exception was raised');
        }
    }
}
