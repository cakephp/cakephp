<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.4
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http;

use Zend\Diactoros\CallbackStream as BaseCallbackStream;

/**
 * Implementation of PSR HTTP streams.
 *
 * This differs from Zend\Diactoros\Callback stream in that
 * it allows the use of `echo` inside the callback, and gracefully
 * handles the callback not returning a string.
 *
 * Ideally we can amend/update diactoros, but we need to figure
 * that out with the diactoros project. Until then we'll use this shim
 * to provide backwards compatiblity with existing CakePHP apps.
 *
 * @internal
 */
class CallbackStream extends BaseCallbackStream
{
    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        $callback = $this->detach();
        $result = $callback ? $callback() : '';
        if (!is_string($result)) {
            return '';
        }
        return $result;
    }
}
