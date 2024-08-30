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

class MailerSendWithUnsetTemplateDefaultsToActionNameTest extends TestCase
{
    public function testSendAction(): void
    {
        $mailer = new class extends Mailer {
            public bool $testIsCalled = false;

            public function test($to, $subject)
            {
                $this->testIsCalled = true;
            }

            public function deliver(string $content = ''): array
            {
                return [];
            }

            protected function restore()
            {
                return $this;
            }
        };

        $mailer->send('test', ['foo', 'bar']);
        $this->assertSame('test', $mailer->viewBuilder()->getTemplate());
        $this->assertTrue($mailer->testIsCalled);
    }
}
