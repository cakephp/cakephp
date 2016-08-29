<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.3.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Mailer;

/**
 * Test Suite Test App Mailer class.
 */
class TestUserMailer extends TestMailer
{

    public function invite($email)
    {
        $this->_email
            ->subject('CakePHP')
            ->from('jadb@cakephp.org')
            ->to($email)
            ->cc('markstory@cakephp.org')
            ->addCc('admad@cakephp.org', 'Adnan')
            ->bcc('dereuromark@cakephp.org', 'Mark')
            ->addBcc('antograssiot@cakephp.org')
            ->attachments([
                dirname(__FILE__) . DS . 'TestMailer.php',
                dirname(__FILE__) . DS . 'TestUserMailer.php'
            ])
            ->send('Hello ' . $email);
    }
}
