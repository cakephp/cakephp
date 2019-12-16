<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Auth;

use Cake\Http\Client\Request;

/**
 * Basic authentication adapter for Cake\Http\Client
 *
 * Generally not directly constructed, but instead used by Cake\Http\Client
 * when $options['auth']['type'] is 'basic'
 */
class Basic
{
    /**
     * Add Authorization header to the request.
     *
     * @param \Cake\Http\Client\Request $request Request instance.
     * @param array $credentials Credentials.
     * @return \Cake\Http\Client\Request The updated request.
     * @see https://www.ietf.org/rfc/rfc2617.txt
     */
    public function authentication(Request $request, array $credentials)
    {
        if (isset($credentials['username'], $credentials['password'])) {
            $value = $this->_generateHeader($credentials['username'], $credentials['password']);
            $request = $request->withHeader('Authorization', $value);
        }

        return $request;
    }

    /**
     * Proxy Authentication
     *
     * @param \Cake\Http\Client\Request $request Request instance.
     * @param array $credentials Credentials.
     * @return \Cake\Http\Client\Request The updated request.
     * @see https://www.ietf.org/rfc/rfc2617.txt
     */
    public function proxyAuthentication(Request $request, array $credentials)
    {
        if (isset($credentials['username'], $credentials['password'])) {
            $value = $this->_generateHeader($credentials['username'], $credentials['password']);
            $request = $request->withHeader('Proxy-Authorization', $value);
        }

        return $request;
    }

    /**
     * Generate basic [proxy] authentication header
     *
     * @param string $user Username.
     * @param string $pass Password.
     * @return string
     */
    protected function _generateHeader($user, $pass)
    {
        return 'Basic ' . base64_encode($user . ':' . $pass);
    }
}

// @deprecated 3.4.0 Add backwards compat alias.
class_alias('Cake\Http\Client\Auth\Basic', 'Cake\Network\Http\Auth\Basic');
