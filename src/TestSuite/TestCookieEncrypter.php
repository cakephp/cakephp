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

use Cake\Controller\Component\CookieComponent;
use Cake\Controller\ComponentRegistry;

class TestCookieEncrypter extends CookieComponent {
    public function __construct() {
        return parent::__construct(new ComponentRegistry());
    }

    public function encrypt($value, $encrypt)
    {
        return $this->_encrypt($value, $encrypt);
    }
}