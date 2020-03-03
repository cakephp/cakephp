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
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\Exception\MissingMailerException;
use Cake\TestSuite\TestCase;
use TestApp\Mailer\Stub;
use TestApp\Mailer\TestMailer;

/**
 * MailerAwareTrait test case
 */
class MailerAwareTraitTest extends TestCase
{
    /**
     * Test getMailer
     *
     * @return void
     */
    public function testGetMailer()
    {
        $originalAppNamespace = Configure::read('App.namespace');
        static::setAppNamespace();

        $stub = new Stub();
        $this->assertInstanceOf(TestMailer::class, $stub->getMailer('Test'));

        $stub = new Stub();
        $mailer = $stub->getMailer('Test', ['from' => 'admad@cakephp.org']);
        $this->assertSame(['admad@cakephp.org' => 'admad@cakephp.org'], $mailer->getFrom());

        static::setAppNamespace($originalAppNamespace);
    }

    /**
     * Test exception thrown by getMailer.
     */
    public function testGetMailerThrowsException()
    {
        $this->expectException(MissingMailerException::class);
        $this->expectExceptionMessage('Mailer class "Test" could not be found.');
        $stub = new Stub();
        $stub->getMailer('Test');
    }
}
