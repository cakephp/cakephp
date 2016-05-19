<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Http;

use Cake\Network\Http\Request;

/**
 * Testing stub to ensure that auth providers
 * that mutate requests in place continue to work.
 *
 * @deprecated 3.3.0 Remove this compatibility behavior in 4.0.0
 */
class CompatAuth
{

    /**
     * Add Authorization header to the request via in-place mutation methods.
     *
     * @param \Cake\Network\Http\Request $request Request instance.
     * @param array $credentials Credentials.
     * @return \Cake\Network\Http\Request The updated request.
     */
    public function authentication(Request $request, array $credentials)
    {
        $request->header('Authorization', 'Bearer abc123');
    }

    /**
     * Proxy Authentication added via in-place mutation methods.
     *
     * @param \Cake\Network\Http\Request $request Request instance.
     * @param array $credentials Credentials.
     * @return \Cake\Network\Http\Request The updated request.
     */
    public function proxyAuthentication(Request $request, array $credentials)
    {
        $request->header('Proxy-Authorization', 'Bearer abc123');
    }
}
