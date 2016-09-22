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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Network\Request;

/**
 * Digest Authentication adapter for AuthComponent.
 *
 * Provides Digest HTTP authentication support for AuthComponent.
 *
 * ### Using Digest auth
 *
 * In your controller's components array, add auth + the required config
 * ```
 *  public $components = [
 *      'Auth' => [
 *          'authenticate' => ['Digest']
 *      ]
 *  ];
 * ```
 *
 * You should also set `AuthComponent::$sessionKey = false;` in your AppController's
 * beforeFilter() to prevent CakePHP from sending a session cookie to the client.
 *
 * Since HTTP Digest Authentication is stateless you don't need a login() action
 * in your controller. The user credentials will be checked on each request. If
 * valid credentials are not provided, required authentication headers will be sent
 * by this authentication provider which triggers the login dialog in the browser/client.
 *
 * You may also want to use `$this->Auth->unauthorizedRedirect = false;`.
 * This causes AuthComponent to throw a ForbiddenException exception instead of
 * redirecting to another page.
 *
 * ### Generating passwords compatible with Digest authentication.
 *
 * DigestAuthenticate requires a special password hash that conforms to RFC2617.
 * You can generate this password using `DigestAuthenticate::password()`
 *
 * ```
 * $digestPass = DigestAuthenticate::password($username, $password, env('SERVER_NAME'));
 * ```
 *
 * If you wish to use digest authentication alongside other authentication methods,
 * it's recommended that you store the digest authentication separately. For
 * example `User.digest_pass` could be used for a digest password, while
 * `User.password` would store the password hash for use with other methods like
 * Basic or Form.
 */
class DigestAuthenticate extends BasicAuthenticate
{

    /**
     * Constructor
     *
     * Besides the keys specified in BaseAuthenticate::$_defaultConfig,
     * DigestAuthenticate uses the following extra keys:
     *
     * - `realm` The realm authentication is for, Defaults to the servername.
     * - `nonce` A nonce used for authentication. Defaults to `uniqid()`.
     * - `qop` Defaults to 'auth', no other values are supported at this time.
     * - `opaque` A string that must be returned unchanged by clients.
     *    Defaults to `md5($config['realm'])`
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_registry = $registry;

        $this->config([
            'realm' => null,
            'qop' => 'auth',
            'nonce' => uniqid(''),
            'opaque' => null,
        ]);

        $this->config($config);
    }

    /**
     * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
     *
     * @param \Cake\Network\Request $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(Request $request)
    {
        $digest = $this->_getDigest($request);
        if (empty($digest)) {
            return false;
        }

        $user = $this->_findUser($digest['username']);
        if (empty($user)) {
            return false;
        }

        $field = $this->_config['fields']['password'];
        $password = $user[$field];
        unset($user[$field]);

        $hash = $this->generateResponseHash($digest, $password, $request->env('ORIGINAL_REQUEST_METHOD'));
        if ($digest['response'] === $hash) {
            return $user;
        }

        return false;
    }

    /**
     * Gets the digest headers from the request/environment.
     *
     * @param \Cake\Network\Request $request Request object.
     * @return array Array of digest information.
     */
    protected function _getDigest(Request $request)
    {
        $digest = $request->env('PHP_AUTH_DIGEST');
        if (empty($digest) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers['Authorization']) && substr($headers['Authorization'], 0, 7) === 'Digest ') {
                $digest = substr($headers['Authorization'], 7);
            }
        }
        if (empty($digest)) {
            return false;
        }

        return $this->parseAuthData($digest);
    }

    /**
     * Parse the digest authentication headers and split them up.
     *
     * @param string $digest The raw digest authentication headers.
     * @return array|null An array of digest authentication headers
     */
    public function parseAuthData($digest)
    {
        if (substr($digest, 0, 7) === 'Digest ') {
            $digest = substr($digest, 7);
        }
        $keys = $match = [];
        $req = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
        preg_match_all('/(\w+)=([\'"]?)([a-zA-Z0-9\:\#\%\?\&@=\.\/_-]+)\2/', $digest, $match, PREG_SET_ORDER);

        foreach ($match as $i) {
            $keys[$i[1]] = $i[3];
            unset($req[$i[1]]);
        }

        if (empty($req)) {
            return $keys;
        }

        return null;
    }

    /**
     * Generate the response hash for a given digest array.
     *
     * @param array $digest Digest information containing data from DigestAuthenticate::parseAuthData().
     * @param string $password The digest hash password generated with DigestAuthenticate::password()
     * @param string $method Request method
     * @return string Response hash
     */
    public function generateResponseHash($digest, $password, $method)
    {
        return md5(
            $password .
            ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' .
            md5($method . ':' . $digest['uri'])
        );
    }

    /**
     * Creates an auth digest password hash to store
     *
     * @param string $username The username to use in the digest hash.
     * @param string $password The unhashed password to make a digest hash for.
     * @param string $realm The realm the password is for.
     * @return string the hashed password that can later be used with Digest authentication.
     */
    public static function password($username, $password, $realm)
    {
        return md5($username . ':' . $realm . ':' . $password);
    }

    /**
     * Generate the login headers
     *
     * @param \Cake\Network\Request $request Request object.
     * @return string Headers for logging in.
     */
    public function loginHeaders(Request $request)
    {
        $realm = $this->_config['realm'] ?: $request->env('SERVER_NAME');

        $options = [
            'realm' => $realm,
            'qop' => $this->_config['qop'],
            'nonce' => $this->_config['nonce'],
            'opaque' => $this->_config['opaque'] ?: md5($realm)
        ];

        $opts = [];
        foreach ($options as $k => $v) {
            $opts[] = sprintf('%s="%s"', $k, $v);
        }

        return 'WWW-Authenticate: Digest ' . implode(',', $opts);
    }
}
