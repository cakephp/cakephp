<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\CookieComponent;

/**
 * Class TestCookieEncrypter
 *
 * This class is to encrypt a cookie value in the IntegrationTestCase as if CookieComponent would do.
 *
 * @package Cake\TestSuite
 */
class TestCookieEncrypter extends CookieComponent
{
    /**
     * TestCookieEncrypter constructor.
     */
    public function __construct()
    {
        return parent::__construct(new ComponentRegistry());
    }

    /**
     * Encrypt a cookie value as if CookieComponent would do.
     *
     * @param string $value Value to encrypt
     * @param string|bool $encrypt Encryption mode to use. False
     *   disabled encryption.
     * @return string Encoded values
     */
    public function encrypt($value, $encrypt)
    {
        return $this->_encrypt($value, $encrypt);
    }
}
