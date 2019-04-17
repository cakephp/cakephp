<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth;

use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Security;

/**
 * Digest Authentication adapter for AuthComponent.
 *
 * Provides Digest HTTP authentication support for AuthComponent.
 *
 * ### Using Digest auth
 *
 * Load `AuthComponent` in your controller's `initialize()` and add 'Digest' in 'authenticate' key
 *
 * ```
 *  $this->loadComponent('Auth', [
 *      'authenticate' => ['Digest'],
 *      'storage' => 'Memory',
 *      'unauthorizedRedirect' => false,
 *  ]);
 * ```
 *
 * You should set `storage` to `Memory` to prevent CakePHP from sending a
 * session cookie to the client.
 *
 * You should set `unauthorizedRedirect` to `false`. This causes `AuthComponent` to
 * throw a `ForbiddenException` exception instead of redirecting to another page.
 *
 * Since HTTP Digest Authentication is stateless you don't need call `setUser()`
 * in your controller. The user credentials will be checked on each request. If
 * valid credentials are not provided, required authentication headers will be sent
 * by this authentication provider which triggers the login dialog in the browser/client.
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
 *
 * @see https://book.cakephp.org/3.0/en/controllers/components/authentication.html
 */
class DigestAuthenticate extends BasicAuthenticate
{

    /**
     * Constructor
     *
     * Besides the keys specified in BaseAuthenticate::$_defaultConfig,
     * DigestAuthenticate uses the following extra keys:
     *
     * - `secret` The secret to use for nonce validation. Defaults to Security::getSalt().
     * - `realm` The realm authentication is for, Defaults to the servername.
     * - `qop` Defaults to 'auth', no other values are supported at this time.
     * - `opaque` A string that must be returned unchanged by clients.
     *    Defaults to `md5($config['realm'])`
     * - `nonceLifetime` The number of seconds that nonces are valid for. Defaults to 300.
     *
     * @param \Cake\Controller\ComponentRegistry $registry The Component registry
     *   used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->setConfig([
            'nonceLifetime' => 300,
            'secret' => Security::getSalt(),
            'realm' => null,
            'qop' => 'auth',
            'opaque' => null,
        ]);

        parent::__construct($registry, $config);
    }

    /**
     * Get a user based on information in the request. Used by cookie-less auth for stateless clients.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(ServerRequest $request)
    {
        $digest = $this->_getDigest($request);
        if (empty($digest)) {
            return false;
        }

        $user = $this->_findUser($digest['username']);
        if (empty($user)) {
            return false;
        }

        if (!$this->validNonce($digest['nonce'])) {
            return false;
        }

        $field = $this->_config['fields']['password'];
        $password = $user[$field];
        unset($user[$field]);

        $hash = $this->generateResponseHash($digest, $password, $request->getEnv('ORIGINAL_REQUEST_METHOD'));
        if (hash_equals($hash, $digest['response'])) {
            return $user;
        }

        return false;
    }

    /**
     * Gets the digest headers from the request/environment.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return array|bool Array of digest information.
     */
    protected function _getDigest(ServerRequest $request)
    {
        $digest = $request->getEnv('PHP_AUTH_DIGEST');
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
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return array Headers for logging in.
     */
    public function loginHeaders(ServerRequest $request)
    {
        $realm = $this->_config['realm'] ?: $request->getEnv('SERVER_NAME');

        $options = [
            'realm' => $realm,
            'qop' => $this->_config['qop'],
            'nonce' => $this->generateNonce(),
            'opaque' => $this->_config['opaque'] ?: md5($realm)
        ];

        $digest = $this->_getDigest($request);
        if ($digest && isset($digest['nonce']) && !$this->validNonce($digest['nonce'])) {
            $options['stale'] = true;
        }

        $opts = [];
        foreach ($options as $k => $v) {
            if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
                $opts[] = sprintf('%s=%s', $k, $v);
            } else {
                $opts[] = sprintf('%s="%s"', $k, $v);
            }
        }

        return [
            'WWW-Authenticate' => 'Digest ' . implode(',', $opts)
        ];
    }

    /**
     * Generate a nonce value that is validated in future requests.
     *
     * @return string
     */
    protected function generateNonce()
    {
        $expiryTime = microtime(true) + $this->getConfig('nonceLifetime');
        $secret = $this->getConfig('secret');
        $signatureValue = hash_hmac('sha256', $expiryTime . ':' . $secret, $secret);
        $nonceValue = $expiryTime . ':' . $signatureValue;

        return base64_encode($nonceValue);
    }

    /**
     * Check the nonce to ensure it is valid and not expired.
     *
     * @param string $nonce The nonce value to check.
     * @return bool
     */
    protected function validNonce($nonce)
    {
        $value = base64_decode($nonce);
        if ($value === false) {
            return false;
        }
        $parts = explode(':', $value);
        if (count($parts) !== 2) {
            return false;
        }
        list($expires, $checksum) = $parts;
        if ($expires < microtime(true)) {
            return false;
        }
        $secret = $this->getConfig('secret');
        $check = hash_hmac('sha256', $expires . ':' . $secret, $secret);

        return hash_equals($check, $checksum);
    }
}
