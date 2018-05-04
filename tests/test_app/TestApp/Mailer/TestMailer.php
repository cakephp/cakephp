<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Mailer;

use Cake\Mailer\Mailer;

/**
 * Test Suite Test App Mailer class.
 */
class TestMailer extends Mailer
{

    public function getEmailForAssertion()
    {
        return $this->_email;
    }

    public function reset()
    {
        $this->template = $this->viewBuilder()->getTemplate();

        return parent::reset();
    }
}
